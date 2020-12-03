<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\PostgreSQL;

use NoreSources\Container;
use NoreSources\SQL\DBMS\AbstractStructureExplorer;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\ConnectionProviderInterface;
use NoreSources\SQL\DBMS\PlatformInterface;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConstants as K;
use NoreSources\SQL\DBMS\Traits\ConnectionProviderTrait;
use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Structure\ArrayColumnDescription;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\IndexTableConstraint;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\StructureElementIdentifier;
use NoreSources\SQL\Syntax\Data;

class PostgreSQLStructureExplorer extends AbstractStructureExplorer implements
	ConnectionProviderInterface
{
	use ConnectionProviderTrait;

	public function __construct(ConnectionInterface $connection)
	{
		$this->setConnection($connection);
	}

	public function getNamespaceNames()
	{
		$sql = "SELECT nspname FROM pg_namespace WHERE nspname !~ '^pg_.*' AND nspname != 'information_schema'";
		$recordset = $this->getConnection()->executeStatement($sql);
		return self::recordsetToList($recordset, 'nspname');
	}

	public function getTableNames($parentIdentifier = null)
	{
		$parentIdentifier = StructureElementIdentifier::make(
			$parentIdentifier ? $parentIdentifier : 'public');

		$platform = $this->getConnection()->getPlatform();

		$sql = \sprintf(
			"SELECT table_name FROM information_schema.tables" .
			" WHERE  table_schema NOT LIKE 'pg\_%%'" .
			" AND table_schema != 'information_schema'" .
			" AND table_name != 'geometry_columns'" .
			" AND table_name != 'spatial_ref_sys'" .
			" AND table_type != 'VIEW' AND	table_schema = %s",
			$platform->quoteStringValue($parentIdentifier));

		$recordset = $this->getConnection()->executeStatement($sql);
		return self::recordsetToList($recordset, 'table_name');
	}

	public function getTableColumnNames($tableIdentifier)
	{
		$tableIdentifier = StructureElementIdentifier::make(
			$tableIdentifier);
		$tableName = $tableIdentifier->getLocalName();
		$namespace = $tableIdentifier->getParentIdentifier();
		if (empty($namespace->getPath()))
			$namespace = 'public';
		$platform = $this->getConnection()->getPlatform();

		$sql = sprintf(
			'SELECT column_name' . ' FROM information_schema.columns' .
			' WHERE table_name=%s' . ' AND table_schema=%s',
			$platform->quoteStringValue($tableName),
			$platform->quoteStringValue($namespace));
		$recordset = $this->getConnection()->executeStatement($sql);

		return self::recordsetToList($recordset);
	}

	public function getTableColumn($tableIdentifier, $columnName)
	{
		$tableIdentifier = StructureElementIdentifier::make(
			$tableIdentifier);
		$tableName = $tableIdentifier->getLocalName();
		$namespace = $tableIdentifier->getParentIdentifier();
		if (empty($namespace->getPath()))
			$namespace = 'public';
		$platform = $this->getConnection()->getPlatform();

		$sql = sprintf(
			'SELECT column_default, is_nullable,
				data_type, character_maximum_length,
				numeric_precision, numeric_scale
				FROM information_schema.columns
				WHERE table_schema=%s
					AND table_name=%s
					AND column_name=%s', $platform->quoteStringValue($namespace),
			$platform->quoteStringValue($tableName),
			$platform->quoteStringValue($columnName));
		$recordset = $this->getConnection()->executeStatement($sql);

		$recordset = $this->getConnection()->executeStatement($sql);
		$recordset->setFlags(K::RECORDSET_FETCH_ASSOCIATIVE);
		$info = $recordset->current();

		$type = $platform->getTypeRegistry()->get($info['data_type']);
		$dataType = $type->get(K::TYPE_DATA_TYPE);
		$flags = 0;
		if (\strcasecmp($info['is_nullable'], 'yes'))
			$dataType |= K::DATATYPE_NULL;

		if ($dataType & K::DATATYPE_STRING)
		{
			if (!empty($info['character_maximum_length']))
			{
				$properties[K::COLUMN_LENGTH] = \intval(
					$info['character_maximum_length']);
			}
		}
		elseif ($dataType & K::DATATYPE_NUMBER)
		{
			if (!empty($info['numeric_precision']))
			{
				$properties[K::COLUMN_PRECISION] = \intval(
					$info['numeric_precision']);

				if (!empty($info['numeric_scale']))
				{
					$scale = \intval($info['numeric_scale']);
					if ($scale)
						$properties[K::COLUMN_FRACTION_SCALE] = $scale;
				}
			}
		}

		$properties = [
			K::COLUMN_NAME => $columnName
		];

		if (!empty($info['column_default']))
		{
			if (\preg_match('/nextval\(.*\)$/', $info['column_default']))
			{
				$flags |= K::COLUMN_FLAG_AUTO_INCREMENT;
			}
			else
			{
				$sql = 'SELECT ' . $info['column_default'] .
					' AS "value"';
				$recordset = $this->getConnection()->executeStatement(
					$sql);

				$recordset->getResultColumns()->offsetSet(0,
					new ArrayColumnDescription(
						[
							K::COLUMN_NAME => 'value',
							K::COLUMN_DATA_TYPE => $dataType
						]));
				$recordset->setFlags(
					$recordset->getFlags() |
					K::RECORDSET_FETCH_UBSERIALIZE);
				$properties[K::COLUMN_DEFAULT_VALUE] = new Data(
					self::recordsetToValue($recordset), $dataType);
			}
		}

		$properties[K::COLUMN_DATA_TYPE] = $dataType;
		if ($flags)
			$properties[K::COLUMN_FLAGS] = $flags;

		return new ArrayColumnDescription($properties);
	}

	public function getTablePrimaryKeyConstraint($tableIdentifier)
	{
		$tableIdentifier = StructureElementIdentifier::make(
			$tableIdentifier);
		$tableName = $tableIdentifier->getLocalName();
		$namespace = $tableIdentifier->getParentIdentifier();
		if (empty($namespace->getPath()))
			$namespace = 'public';
		$platform = $this->getConnection()->getPlatform();

		$sql = sprintf(
			'SELECT
			    tc.constraint_name as name,
			    kcu.column_name as column
			FROM
			    information_schema.table_constraints AS tc
			    INNER JOIN information_schema.key_column_usage AS kcu
			      ON tc.constraint_name = kcu.constraint_name
			      AND tc.table_schema = kcu.table_schema
				WHERE tc.constraint_type=%s
						AND tc.table_schema=%s
						AND tc.table_name=%s', $platform->quoteStringValue('PRIMARY KEY'),
			$platform->quoteStringValue($namespace),
			$platform->quoteStringValue($tableName));

		$recordset = $this->getConnection()->executeStatement($sql);
		$recordset->setFlags(
			K::RECORDSET_FETCH_ASSOCIATIVE |
			K::RECORDSET_FETCH_UBSERIALIZE);

		$primaryKey = null;

		foreach ($recordset as $row)
		{
			$name = $row['name'];
			$column = $row['column'];

			if (!isset($primaryKey))
			{
				$primaryKey = new PrimaryKeyTableConstraint();
				$primaryKey->setName($name);
			}

			$primaryKey->append($column);
		}

		return $primaryKey;
	}

	public function getTableForeignKeyConstraints($tableIdentifier)
	{
		$tableIdentifier = StructureElementIdentifier::make(
			$tableIdentifier);
		$tableName = $tableIdentifier->getLocalName();
		$namespace = $tableIdentifier->getParentIdentifier();
		if (empty($namespace->getPath()))
			$namespace = 'public';
		$platform = $this->getConnection()->getPlatform();

		$actionMap = [
			'CASCADE' => K::FOREIGN_KEY_ACTION_CASCADE,
			'RESTRICT' => K::FOREIGN_KEY_ACTION_RESTRICT,
			'SET DEFAULT' => K::FOREIGN_KEY_ACTION_SET_DEFAULT,
			'SET NULL' => K::FOREIGN_KEY_ACTION_SET_NULL
		];

		$sql = sprintf(
			"SELECT
				c.conname as name
       		FROM pg_catalog.pg_constraint c
			INNER JOIN pg_catalog.pg_class r
            	ON r.oid = c.conrelid
			INNER JOIN pg_catalog.pg_namespace n
				ON n.oid = connamespace
			WHERE c.contype = 'f'
				AND n.nspname=%s
				AND r.relname=%s
			", $platform->quoteStringValue($namespace),
			$platform->quoteStringValue($tableName));

		$recordset = $this->getConnection()->executeStatement($sql);
		$names = self::recordsetToList($recordset);

		$foreignKeys = [];
		foreach ($names as $name)
		{
			$columns = $this->getTableConstraintColumns($namespace,
				$tableName, $name);

			$ref = Container::firstValue($columns);
			$fk = new ForeignKeyTableConstraint(
				StructureElementIdentifier::make(
					[
						$ref['namespace'],
						$ref['table']
					]));
			$fk->setName($name);
			foreach ($columns as $column => $reference)
			{
				$fk->addColumn($column, $reference['column']);
			}

			$sql = sprintf(
				"SELECT
					update_rule as update,
					delete_rule as delete
				FROM information_schema.referential_constraints
					WHERE constraint_schema=%s
					AND constraint_name=%s", $platform->quoteStringValue($namespace),
				$platform->quoteStringValue($name));

			$rules = $this->getConnection()->executeStatement($sql);
			if (($rules = $rules->current()))
			{
				if ($action = Container::keyValue($actionMap,
					$rules['update']))
					$fk->getEvents()->on(K::EVENT_UPDATE, $action);
				if ($action = Container::keyValue($actionMap,
					$rules['delete']))
					$fk->getEvents()->on(K::EVENT_DELETE, $action);
			}

			$foreignKeys[] = $fk;
		}

		return $foreignKeys;
	}

	public function getTableIndexNames($tableIdentifier)
	{
		$tableIdentifier = StructureElementIdentifier::make(
			$tableIdentifier);
		$tableName = $tableIdentifier->getLocalName();
		$namespace = $tableIdentifier->getParentIdentifier();
		if (empty($namespace->getPath()))
			$namespace = 'public';
		$platform = $this->getConnection()->getPlatform();

		$namespace = $platform->quoteStringValue($namespace);
		$tableName = $platform->quoteStringValue($tableName);

		$sql = sprintf(
			'SELECT
				i.indexname as name
			FROM
				pg_catalog.pg_indexes i
			WHERE i.schemaname=%s
				AND i.tablename=%s
				AND i.indexname NOT IN (SELECT constraint_name
						FROM information_schema.table_constraints
						WHERE constraint_schema=%s
							AND table_name=%s)', $namespace, $tableName, $namespace,
			$tableName);

		return self::recordsetToList(
			$this->getConnection()->executeStatement($sql));
	}

	public function getTableIndexes($tableIdentifier)
	{
		$tableIdentifier = StructureElementIdentifier::make(
			$tableIdentifier);
		$tableName = $tableIdentifier->getLocalName();
		$namespace = $tableIdentifier->getParentIdentifier();
		if (empty($namespace->getPath()))
			$namespace = 'public';

		$tableOID = $this->getCatalogClassTableId($namespace, $tableName);

		$sql = sprintf(
			'SELECT
				c.oid,
				c.relname as name,
				i.indisunique as unique
			FROM
				pg_catalog.pg_index i
				INNER JOIN pg_catalog.pg_class c
					ON i.indexrelid = c.oid
			WHERE i.indrelid=%d
				AND NOT (i.indisprimary)
				AND i.indisvalid
', $tableOID);

		$recordset = $this->getConnection()->executeStatement($sql);
		$recordset->setFlags(
			K::RECORDSET_FETCH_ASSOCIATIVE |
			K::RECORDSET_FETCH_UBSERIALIZE);

		$indexes = [];
		foreach ($recordset as $row)
		{
			$index = new IndexTableConstraint();
			$index->setName($row['name']);
			$index->unique($row['unique'] == 't');

			$sql = sprintf(
				'SELECT attname as name
					FROM pg_catalog.pg_attribute
					WHERE attrelid=%d
					', \intval($row['oid']));

			$r = $this->getConnection()->executeStatement($sql);
			foreach ($r as $crow)
			{
				$name = $crow['name'];
				$index->append($name);
			}

			$indexes[] = $index;
		}

		return $indexes;
	}

	public function getViewNames($parentIdentifier = null)
	{
		/**
		 *
		 * @var StructureElementIdentifier $parentIdentifier
		 */
		$namespace = StructureElementIdentifier::make($parentIdentifier);
		if (empty($namespace->getPath()))
			$namespace = 'public';

		$sql = sprintf(
			'SELECT
				table_name as name
				FROM information_schema.views
				WHERE table_schema=%s',
			$this->getConnection()
				->getPlatform()
				->quoteStringValue($namespace));

		$recordset = $this->getConnection()->executeStatement($sql);
		return self::recordsetToList($recordset);
	}

	protected function getCatalogClassTableId($namespace, $table)
	{
		$sql = sprintf(
			'SELECT pg_catalog.pg_class.oid
				FROM pg_catalog.pg_class
				INNER JOIN pg_catalog.pg_namespace
					ON pg_catalog.pg_class.relnamespace = pg_catalog.pg_namespace.oid
				WHERE %s', $this->catalogClassTableCriteria($namespace, $table));
		$recordset = $this->getConnection()->executeStatement($sql);
		$recordset->setFlags(
			K::RECORDSET_FETCH_ASSOCIATIVE |
			K::RECORDSET_FETCH_UBSERIALIZE);
		return self::recordsetToValue($recordset, 'oid');
	}

	/**
	 *
	 * @param PlatformInterface $platform
	 * @param string $namespace
	 * @param string $table
	 * @param string $catalogClassTable
	 * @param string $catalogNamespaceTable
	 * @return string
	 */
	protected function catalogClassTableCriteria($namespace, $table,
		$catalogClassTable = 'pg_catalog.pg_class',
		$catalogNamespaceTable = 'pg_catalog.pg_namespace')
	{
		$platform = $this->getConnection()->getPlatform();
		$catalogClassTable = StructureElementIdentifier::make(
			$catalogClassTable);

		$catalogNamespaceTable = StructureElementIdentifier::make(
			$catalogNamespaceTable);

		return sprintf(
			'%s.nspname=%s
				AND %s.relname=%s
				AND %s.relkind=%s',
			$platform->quoteIdentifierPath($catalogNamespaceTable),
			$platform->quoteStringValue($namespace),
			$platform->quoteIdentifierPath($catalogClassTable),
			$platform->quoteStringValue($table),
			$platform->quoteIdentifierPath($catalogClassTable),
			$platform->quoteStringValue('r'));
	}

	protected function getTableConstraintColumns($namespace, $table,
		$name)
	{
		$platform = $this->getConnection()->getPlatform();
		$sql = sprintf(
			"SELECT
    			kcu.column_name as column,
    			ccu.table_schema AS ref_namespace,
    			ccu.table_name AS ref_table,
    			ccu.column_name AS ref_column
			FROM
    			information_schema.table_constraints AS tc
			    JOIN information_schema.key_column_usage AS kcu
					ON tc.constraint_name = kcu.constraint_name
					AND tc.table_schema = kcu.table_schema
				JOIN information_schema.constraint_column_usage AS ccu
					ON ccu.constraint_name = tc.constraint_name
					AND ccu.table_schema = tc.table_schema
				WHERE tc.table_schema=%s
					AND tc.table_name=%s
					AND tc.constraint_name=%s", $platform->quoteStringValue($namespace),
			$platform->quoteStringValue($table),
			$platform->quoteStringValue($name));

		$recordset = $this->getConnection()->executeStatement($sql);
		$recordset->setFlags(K::RECORDSET_FETCH_ASSOCIATIVE);
		$columns = [];
		foreach ($recordset as $row)
		{
			$columns[$row['column']] = [
				"namespace" => $row['ref_namespace'],
				'table' => $row['ref_table'],
				'column' => $row['ref_column']
			];
		}

		return $columns;
	}

	protected static function recordsetToList(Recordset $recordset,
		$columnName = 0)
	{
		return Container::map($recordset,
			function ($index, $row) use ($columnName) {
				return $row[$columnName];
			});
	}

	protected static function recordsetToValue(Recordset $recordset,
		$columnName = 0)
	{
		$list = self::recordsetToList($recordset, $columnName);
		return Container::firstValue($list);
	}
}
