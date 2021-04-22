<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\Container;
use NoreSources\SQL\DBMS\AbstractStructureExplorer;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\ConnectionProviderInterface;
use NoreSources\SQL\DBMS\InformationSchemaStructureExplorerTrait;
use NoreSources\SQL\DBMS\MySQL\MySQLConstants as K;
use NoreSources\SQL\DBMS\Traits\ConnectionProviderTrait;
use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\IndexTableConstraint;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\StructureElementIdentifier;

class MySQLStructureExplorer extends AbstractStructureExplorer implements
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
		return self::recordsetToList(
			$this->getConnection()->executeStatement('SHOW DATABASES'),
			0);
	}

	public function getTableNames($parentIdentifier = null)
	{
		$parentIdentifier = StructureElementIdentifier::make(
			$parentIdentifier);
		if (empty($parentIdentifier->getPath()))
			$parentIdentifier = StructureElementIdentifier::make(
				$this->getCurrentNAmespace());

		if (empty($parentIdentifier->getPath()))
			throw new \InvalidArgumentException(
				'Table namespace is mandatory');

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
			$namespace = $this->getCurrentNAmespace();

		if ($namespace === null)
			throw new \InvalidArgumentException(
				'Table namespace is mandatory');

		return $this->getInformationSchemaTableColumnNames(
			$this->getConnection(),
			StructureElementIdentifier::make([
				$namespace,
				$tableName
			]));
	}

	public function getTableColumn($tableIdentifier, $columnName)
	{
		$tableIdentifier = StructureElementIdentifier::make(
			$tableIdentifier);
		$tableName = $tableIdentifier->getLocalName();
		$namespace = $tableIdentifier->getParentIdentifier();
		if (empty($namespace->getPath()))
			$namespace = $this->getCurrentNAmespace();

		if ($namespace === null)
			throw new \InvalidArgumentException(
				'Table namespace is mandatory');

		return $this->getInformationSchemaTableColumn(
			$this->getConnection(),
			StructureElementIdentifier::make([
				$namespace,
				$tableName
			]), $columnName, [
				'extra'
			]);
	}

	public function getTablePrimaryKeyConstraint($tableIdentifier)
	{
		$tableIdentifier = StructureElementIdentifier::make(
			$tableIdentifier);
		$platform = $this->getConnection()->getPlatform();
		$recordset = $this->getConnection()->executeStatement(
			'SHOW KEYS FROM ' .
			$platform->quoteIdentifierPath($tableIdentifier) .
			" WHERE Key_name = 'PRIMARY'");

		$recordset->setFlags(K::RECORDSET_FETCH_ASSOCIATIVE);

		$columns = Container::map($recordset,
			function ($k, $v) {
				return $v['Column_name'];
			});

		if (\count($columns))
		{
			$pk = new PrimaryKeyTableConstraint($columns);
		}

		return $pk;
	}

	public function getTableForeignKeyConstraints($tableIdentifier)
	{
		$tableIdentifier = StructureElementIdentifier::make(
			$tableIdentifier);
		$tableName = $tableIdentifier->getLocalName();
		$namespace = $tableIdentifier->getParentIdentifier();
		if (empty($namespace->getPath()))
			$namespace = $this->getCurrentNAmespace();

		if ($namespace === null)
			throw new \InvalidArgumentException(
				'Table namespace is mandatory');

		$platform = $this->getConnection()->getPlatform();

		$sql = sprintf(
			'SELECT
				constraint_name,
				column_name,
				referenced_table_schema as reference_namespace,
				referenced_table_name as reference_table,
				referenced_column_name as reference_column
			FROM information_schema.key_column_USAGE
			WHERE table_schema=%s
				AND table_name=%s
				AND constraint_name <> %s
			ORDER BY
				table_schema,
				table_name,
				constraint_name,
				ordinal_position', $platform->quoteStringValue($namespace),
			$platform->quoteStringValue($tableName),
			$platform->quoteStringValue('PRIMARY'));

		$recordset = $this->getConnection()->executeStatement($sql);
		$recordset->setFlags(
			K::RECORDSET_FETCH_ASSOCIATIVE |
			K::RECORDSET_FETCH_UBSERIALIZE);
		$keyed = [];
		foreach ($recordset as $row)
		{
			$name = $row['constraint_name'];
			$column = $row['column_name'];
			$referenceColumn = $row['reference_column'];

			if (!Container::keyExists($keyed, $name))
			{
				$referenceTable = StructureElementIdentifier::make(
					[
						$row['reference_namespace'],
						$row['reference_table']
					]);
				$keyed[$name] = new ForeignKeyTableConstraint(
					$referenceTable);
				$keyed[$name]->setName($name);

				$this->populateInformationSchemaForeignKeyActions(
					$keyed[$name], $this->getConnection(), $namespace);
			}

			$keyed[$name]->addColumn($column, $referenceColumn);
		}

		return Container::values($keyed);
	}

	public function getTableIndexes($tableIdentifier)
	{
		$tableIdentifier = StructureElementIdentifier::make(
			$tableIdentifier);
		$platform = $this->getConnection()->getPlatform();
		$recordset = $this->getConnection()->executeStatement(
			'SHOW KEYS FROM ' .
			$platform->quoteIdentifierPath($tableIdentifier) .
			" WHERE Key_name <> 'PRIMARY'");

		$recordset->setFlags(K::RECORDSET_FETCH_ASSOCIATIVE);

		$keyed = [];
		foreach ($recordset as $row)
		{
			$name = $row['Key_name'];
			$column = $row['Column_name'];
			$i = \intval($row['Seq_in_index']);
			$unique = (\intval($row['Non_unique']) == 0);

			if (!Container::keyExists($keyed, $name))
			{
				$keyed[$name] = new IndexTableConstraint();
				$keyed[$name]->setName($name);
				$keyed[$name]->unique($unique);
			}

			$keyed[$name]->append($column);
		}

		return Container::values($keyed);
	}

	protected function processInformationSchemaColumnInformations(
		&$properties, $info)
	{
		$extra = $info['extra'];
		$flags = Container::keyValue($properties, K::COLUMN_FLAGS, 0);
		if ($extra == 'auto_increment')
		{
			$flags |= K::COLUMN_FLAG_AUTO_INCREMENT;
		}

		if ($flags)
			$properties[K::COLUMN_FLAGS] = $flags;
	}

	/**
	 *
	 * @return mixed|array|unknown[]|\Iterator[]|mixed[]|NULL[]|array[]|\ArrayAccess[]|\Psr\Container\ContainerInterface[]|\Traversable[]
	 */
	protected function getCurrentNAmespace()
	{
		return self::recordsetToValue(
			$this->getConnection()->executeStatement(
				'SELECT DATABASE()'), 0);
	}

	public function getViewNames($parentIdentifier = null)
	{
		$parentIdentifier = StructureElementIdentifier::make(
			$parentIdentifier);
		$namespace = null;

		if (empty($parentIdentifier->getPath()))
			$namespace = $this->getCurrentNAmespace();
		else
			$namespace = $parentIdentifier->getPath();

		$platform = $this->getConnection()->getPlatform();

		$sql = sprintf(
			'SELECT
					table_name
				FROM information_schema.views
				WHERE table_schema=%s
', $platform->quoteStringValue($namespace));

		return self::recordsetToList(
			$this->getConnection()->executeStatement($sql), 0);
	}

	protected static function recordsetToList(Recordset $recordset,
		$columnName = 'name')
	{
		return Container::map($recordset,
			function ($index, $row) use ($columnName) {
				return $row[$columnName];
			});
	}

	protected static function recordsetToValue(Recordset $recordset,
		$columnName = 'name')
	{
		$list = self::recordsetToList($recordset, $columnName);
		return Container::firstValue($list);
	}
}
