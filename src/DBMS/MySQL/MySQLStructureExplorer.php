<?php
/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\Container\Container;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\ConnectionProviderInterface;
use NoreSources\SQL\DBMS\Explorer\AbstractStructureExplorer;
use NoreSources\SQL\DBMS\Explorer\InformationSchemaStructureExplorerTrait;
use NoreSources\SQL\DBMS\Explorer\StructureExplorerException;
use NoreSources\SQL\DBMS\MySQL\MySQLConstants as K;
use NoreSources\SQL\DBMS\Traits\ConnectionProviderTrait;
use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\IndexStructure;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Syntax\Data;
use NoreSources\SQL\Syntax\Keyword;

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
		static $excludes = [
			'information_schema'
		];

		return Container::filter(
			self::recordsetToList(
				$this->getConnection()->executeStatement(
					'SHOW DATABASES'), 0),
			function ($i, $n) use ($excludes) {
				return !\in_array($n, $excludes);
			});
	}

	public function getTableNames($parentIdentifier = null)
	{
		$parentIdentifier = Identifier::make($parentIdentifier);

		if ($parentIdentifier->isEmpty())
			throw new StructureExplorerException(
				'Table namespace is mandatory');

		return $this->getInformationSchemaTableNames(
			$this->getConnection(), $parentIdentifier);
	}

	public function getTableColumnNames($tableIdentifier)
	{
		$tableIdentifier = Identifier::make($tableIdentifier);
		$tableName = $tableIdentifier->getLocalName();
		$namespace = $tableIdentifier->getParentIdentifier();
		if ($namespace->isEmpty())
			throw new StructureExplorerException(
				'Table namespace is mandatory');

		return $this->getInformationSchemaTableColumnNames(
			$this->getConnection(),
			Identifier::make([
				$namespace,
				$tableName
			]));
	}

	public function getTableColumn($tableIdentifier, $columnName)
	{
		$tableIdentifier = Identifier::make($tableIdentifier);
		$tableName = $tableIdentifier->getLocalName();
		$namespace = $tableIdentifier->getParentIdentifier();
		if ($namespace->isEmpty())
			throw new StructureExplorerException(
				'Table namespace is mandatory');

		return $this->getInformationSchemaTableColumn(
			$this->getConnection(), $tableIdentifier, $columnName,
			[
				'extra',
				'column_type'
			]);
	}

	public function getTablePrimaryKeyConstraint($tableIdentifier)
	{
		$tableIdentifier = Identifier::make($tableIdentifier);
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
			return new PrimaryKeyTableConstraint($columns);
		}

		return null;
	}

	public function getTableUniqueConstraints($tableIdentifier)
	{
		$tableIdentifier = Identifier::make($tableIdentifier);
		$tableName = $tableIdentifier->getLocalName();
		$namespace = $tableIdentifier->getParentIdentifier();
		if ($namespace->isEmpty())
			$namespace = 'public';

		return $this->getInformationSchemaTableKeyConstraints(
			$this->getConnection(),
			Identifier::make([
				$namespace,
				$tableName
			]), 'UNIQUE');
	}

	public function getTableForeignKeyConstraints($tableIdentifier)
	{
		$tableIdentifier = Identifier::make($tableIdentifier);
		$tableName = $tableIdentifier->getLocalName();
		$namespace = $tableIdentifier->getParentIdentifier();
		if ($namespace->isEmpty())
			throw new StructureExplorerException(
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
				$referenceTable = Identifier::make(
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
		/** @var Identifier $tableIdentifier */
		$tableIdentifier = Identifier::make($tableIdentifier);

		$platform = $this->getConnection()->getPlatform();

		$columnNames = $this->getInformationSchemaTableColumnNames(
			$this->getConnection(),
			Identifier::make(
				[
					$tableIdentifier->getParentIdentifier(),
					$tableIdentifier->getLocalName()
				]));

		$columnListSQL = Container::implodeValues($columnNames, ', ',
			[
				$platform,
				'quoteStringValue'
			]);

		$sql = sprintf(
			"SHOW KEYS
			FROM %s
			WHERE Key_name <> 'PRIMARY'
			AND Non_unique=1
			AND Key_name NOT IN (%s)
", $platform->quoteIdentifierPath($tableIdentifier), $columnListSQL);
		$recordset = $this->getConnection()->executeStatement($sql);

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
				$keyed[$name] = new IndexStructure($name);
			}

			$keyed[$name]->columns($column);
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

		$typeDeclaration = $info['column_type'];

		/**
		 * MySQL information_schema.columns numeric_precision
		 * returns the MySQL type max length instead of column length
		 * instead of column length.
		 */
		$length = Container::keyValue($properties, K::COLUMN_LENGTH, INF);
		Container::removeKey($properties, K::COLUMN_LENGTH);

		if (\preg_match(
			chr(1) . self::COLUMN_TYPE_DECLARATION_PATTERN . chr(1) . 'i',
			$typeDeclaration, $m))
		{
			if (($precision = \intval(
				Container::keyValue($m, 'precision', 0))) > 0)
			{
				$properties[K::COLUMN_LENGTH] = \intval($precision);
			}

			if (($scale = \intval(Container::keyValue($m, 'scale', 0))) >
				0)
			{
				$properties[K::COLUMN_FRACTION_SCALE] = $scale;
			}

			if (($modifiers = Container::keyValue($m, 'modifiers')))
			{
				if (\preg_match('/(^|\s)unsigned(\s|$)/i', $modifiers))
				{
					$flags |= K::COLUMN_FLAG_UNSIGNED;
				}
			}
		}

		if ($flags)
			$properties[K::COLUMN_FLAGS] = $flags;
	}

	/**
	 *
	 * @return mixed|array|unknown[]|\Iterator[]|mixed[]|NULL[]|array[]|\ArrayAccess[]|\Psr\Container\ContainerInterface[]|\Traversable[]
	 */
	protected function getCurrentNamespace()
	{
		return self::recordsetToValue(
			$this->getConnection()->executeStatement(
				'SELECT DATABASE()'), 0);
	}

	public function getViewNames($parentIdentifier = null)
	{
		$parentIdentifier = Identifier::make($parentIdentifier);

		if ($parentIdentifier->isEmpty())
			throw new StructureExplorerException(
				'Table namespace is mandatory');
		$namespace = $parentIdentifier->getPath();

		$platform = $this->getConnection()->getPlatform();

		$sql = sprintf(
			'SELECT
					table_name
				FROM information_schema.views
				WHERE table_schema=%s
', $platform->quoteStringValue($parentIdentifier));

		return self::recordsetToList(
			$this->getConnection()->executeStatement($sql), 0);
	}

	protected function processInformationSchemaColumnDefault(
		&$properties, $columnValue)
	{
		$platform = $this->getConnection()->getPlatform();
		static $keywords = [
			K::KEYWORD_CURRENT_TIMESTAMP
		];
		foreach ($keywords as $keyword)
		{
			if ($columnValue == $platform->getKeyword($keyword))
			{
				$properties[K::COLUMN_DEFAULT_VALUE] = new Keyword(
					$keyword);
				return;
			}
		}

		$values = [
			K::KEYWORD_NULL => [
				null,
				K::DATATYPE_NULL
			],
			K::KEYWORD_FALSE => [
				false,
				K::DATATYPE_BOOLEAN
			],
			K::KEYWORD_TRUE => [
				true,
				K::DATATYPE_BOOLEAN
			]
		];
		foreach ($values as $k => $p)
		{
			if ($columnValue == $platform->getKeyword($k))
			{
				$properties[K::COLUMN_DEFAULT_VALUE] = new Data($p[0],
					$p[1]);
				return;
			}
		}

		$dataType = Container::keyValue($properties, K::COLUMN_DATA_TYPE,
			K::DATATYPE_UNDEFINED);
		$properties[K::COLUMN_DEFAULT_VALUE] = new Data($columnValue,
			$dataType);
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

	const COLUMN_TYPE_DECLARATION_PATTERN = '(?<name>[a-z][a-z0-9_]*)' .
		'(?:\((?<precision>[1-9][0-9]*)(?:\s*,\s*(?<scale>[0-9]+))?\))?' .
		'(?:\s+(?<modifiers>.*))?';
}
