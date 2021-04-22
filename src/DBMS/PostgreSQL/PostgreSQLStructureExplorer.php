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
use NoreSources\SQL\DBMS\InformationSchemaStructureExplorerTrait;
use NoreSources\SQL\DBMS\PlatformInterface;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConstants as K;
use NoreSources\SQL\DBMS\Traits\ConnectionProviderTrait;
use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Structure\ArrayColumnDescription;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\IndexTableConstraint;
use NoreSources\SQL\Structure\StructureElementIdentifier;
use NoreSources\SQL\Syntax\Data;

class PostgreSQLStructureExplorer extends AbstractStructureExplorer implements
	ConnectionProviderInterface
{
	use ConnectionProviderTrait;
	use InformationSchemaStructureExplorerTrait;

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
		return $this->getInformationSchemaTableNames(
			$this->getConnection(), $parentIdentifier);
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

		return $this->getInformationSchemaTableColumn(
			$this->getConnection(),
			StructureElementIdentifier::make([
				$namespace,
				$tableName
			]), $columnName);
	}

	public function getTablePrimaryKeyConstraint($tableIdentifier)
	{
		$tableIdentifier = StructureElementIdentifier::make(
			$tableIdentifier);
		$tableName = $tableIdentifier->getLocalName();
		$namespace = $tableIdentifier->getParentIdentifier();
		if (empty($namespace->getPath()))
			$namespace = 'public';

		return $this->getInformationSchemaTablePrimaryKeyConstraint(
			$this->getConnection(),
			StructureElementIdentifier::make([
				$namespace,
				$tableName
			]));
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

			$this->populateInformationSchemaForeignKeyActions($fk,
				$this->getConnection(), $namespace);

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
		$namespace = StructureElementIdentifier::make($parentIdentifier);
		if (empty($namespace->getPath()))
			$namespace = StructureElementIdentifier::make('public');

		return $this->getInformationSchemaViewNames(
			$this->getConnection(), $namespace);
	}

	public function processInformationSchemaColumnDefault(&$properties,
		$columnValue)
	{
		if (\preg_match('/nextval\(.*\)$/', $columnValue))
		{
			$flags = Container::keyValue($properties, K::COLUMN_FLAGS, 0);
			$flags |= K::COLUMN_FLAG_AUTO_INCREMENT;
			$properties[K::COLUMN_FLAGS] = $flags;
		}
		else
		{
			$dataType = Container::keyValue($properties,
				K::COLUMN_DATA_TYPE, K::DATATYPE_UNDEFINED);
			$platform = $this->getConnection()->getPlatform();
			$sql = sprintf('SELECT ' . $columnValue . ' AS %s',
				$platform->quoteIdentifier('value'));
			$recordset = $this->getConnection()->executeStatement($sql);
			$recordset->getResultColumns()->offsetSet(0,
				new ArrayColumnDescription(
					[
						K::COLUMN_NAME => 'value',
						K::COLUMN_DATA_TYPE => $dataType
					]));

			$recordset->setFlags(
				K::RECORDSET_FETCH_INDEXED |
				K::RECORDSET_FETCH_UBSERIALIZE);

			$record = $recordset->current();

			$properties[K::COLUMN_DEFAULT_VALUE] = new Data($record[0],
				$dataType);
		}
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
