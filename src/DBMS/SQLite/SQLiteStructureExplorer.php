<?php

/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\Container\Container;
use NoreSources\SQL\DBMS\ConnectionException;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\ConnectionProviderInterface;
use NoreSources\SQL\DBMS\Explorer\AbstractStructureExplorer;
use NoreSources\SQL\DBMS\Explorer\StructureExplorerException;
use NoreSources\SQL\DBMS\SQLite\SQLiteConstants as K;
use NoreSources\SQL\DBMS\Traits\ConnectionProviderTrait;
use NoreSources\SQL\DBMS\Types\ArrayObjectType;
use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Structure\ArrayColumnDescription;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\IndexStructure;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\UniqueTableConstraint;
use NoreSources\SQL\Syntax\Data;
use NoreSources\SQL\Syntax\Keyword;

class SQLiteStructureExplorer extends AbstractStructureExplorer implements
	ConnectionProviderInterface
{
	use ConnectionProviderTrait;

	public function __construct(ConnectionInterface $connection)
	{
		$this->setConnection($connection);
	}

	public function getNamespaceNames()
	{
		$recordset = $this->getConnection()->executeStatement(
			'PRAGMA database_list');
		return Container::filterValues(
			self::recordsetToList($recordset),
			function ($name) {
				return \strcasecmp($name, 'temp') != 0;
			});
	}

	public function getTableNames($parentIdentifier = null)
	{
		$parentIdentifier = Identifier::make($parentIdentifier);
		$master = clone $parentIdentifier;
		$master->append('sqlite_master');

		$platform = $this->getConnection()->getPlatform();

		$fmt = "SELECT name FROM %s WHERE type = 'table'" .
			" AND name != 'sqlite_sequence'" .
			" AND name != 'geometry_columns'" .
			" AND name != 'spatial_ref_sys' ORDER BY name";
		$sql = \sprintf($fmt, $platform->quoteIdentifierPath($master));

		$recordset = $this->getConnection()->executeStatement($sql);
		return self::recordsetToList($recordset);
	}

	public function getTemporaryTableNames($parentIdentifier = null)
	{
		$parentIdentifier = Identifier::make($parentIdentifier);
		if (!$parentIdentifier->isEmpty())
			return [];
		$platform = $this->getConnection()->getPlatform();

		$sql = 'SELECT name FROM sqlite_temp_master' .
			" WHERE type = 'table' " . " ORDER BY name";

		$recordset = $this->getConnection()->executeStatement($sql);
		return self::recordsetToList($recordset);
	}

	public function getTablePrimaryKeyConstraint($tableIdentifier)
	{
		$names = $this->getTableConstraintNames($tableIdentifier,
			'primary key');

		$name = Container::firstValue($names);

		$recordset = $this->scopedAssetPragma('table_info',
			$tableIdentifier, $tableIdentifier);

		$columns = [];
		$recordset->setFlags(
			K::RECORDSET_FETCH_ASSOCIATIVE |
			K::RECORDSET_FETCH_UNSERIALIZE);
		foreach ($recordset as $index => $row)
		{

			if ($row['pk'] == 0)
				continue;

			$columns[] = $row['name'];
		}

		if (\count($row) == 0)
			return null;

		return new PrimaryKeyTableConstraint($columns, $name);
	}

	public function getTableForeignKeyConstraints($tableIdentifier)
	{
		$names = $this->getTableConstraintNames($tableIdentifier,
			'foreign key');

		$list = $this->scopedAssetPragma('foreign_key_list',
			$tableIdentifier);
		$list->setFlags(
			K::RECORDSET_FETCH_ASSOCIATIVE |
			K::RECORDSET_FETCH_UNSERIALIZE);
		$id = -1;
		$foreignKeys = [];
		$current = null;
		$actionMap = [
			'CASCADE' => K::FOREIGN_KEY_ACTION_CASCADE,
			'RESTRICT' => K::FOREIGN_KEY_ACTION_RESTRICT,
			'SET DEFAULT' => K::FOREIGN_KEY_ACTION_SET_DEFAULT,
			'SET NULL' => K::FOREIGN_KEY_ACTION_SET_NULL
		];

		foreach ($list as $row)
		{
			$id = \intval($row['id']);
			if (!Container::keyExists($foreignKeys, $id))
				$foreignKeys[$id] = new ForeignKeyTableConstraint(
					$row['table'], [], Container::keyValue($names, $id));

			if ($row['seq'] == 0)
			{
				$foreignKeys[$id]->getEvents()->on(K::EVENT_UPDATE,
					Container::keyValue($actionMap, $row['on_update']));
				$foreignKeys[$id]->getEvents()->on(K::EVENT_DELETE,
					Container::keyValue($actionMap, $row['on_delete']));
			}

			$foreignKeys[$id]->addColumn($row['from'], $row['to']);
		}

		return $foreignKeys;
	}

	public function getTableUniqueConstraints($tableIdentifier)
	{
		/**
		 *
		 * @var Identifier $tableIdentifier
		 */
		$tableIdentifier = Identifier::make($tableIdentifier);

		$indexes = [];
		$list = $this->scopedAssetPragma('index_list', $tableIdentifier);
		$list->setFlags(
			K::RECORDSET_FETCH_ASSOCIATIVE |
			K::RECORDSET_FETCH_UNSERIALIZE);

		foreach ($list as $info)
		{
			if (\inval($info['unique']) == 0)
				continue;
			$name = $info['name'];
			$parts = $tableIdentifier->getPathParts();
			array_pop($parts);
			array_push($parts, $name);
			$indexIdentifier = Identifier::make($parts);

			$columns = $this->scopedAssetPragmaList('index_info',
				$indexIdentifier);

			$index = new UniqueTableConstraint($columns, $name);
			$index->unique($info['unique'] > 0);

			/**
			 *
			 * @todo Partial index expression parsing
			 */

			$indexes[] = $index;
		}

		return $indexes;
	}

	public function getTableIndexNames($tableIdentifier)
	{
		$tableIdentifier = Identifier::make($tableIdentifier);

		$indexes = [];
		$list = $this->scopedAssetPragma('index_list', $tableIdentifier);
		$list->setFlags(
			K::RECORDSET_FETCH_ASSOCIATIVE |
			K::RECORDSET_FETCH_UNSERIALIZE);

		return Container::map(
			Container::filter($list,
				function ($k, $row) {
					return (\intval($row['unique']) == 0);
				}), function ($k, $row) {
				return $row['name'];
			});
	}

	/**
	 *
	 * @todo
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\DBMS\AbstractStructureExplorer::getTableIndexes()
	 */
	public function getTableIndexes($tableIdentifier)
	{
		/** @var Identifier $tableIdentifier */
		$tableIdentifier = Identifier::make($tableIdentifier);
		$names = $this->getTableIndexNames($tableIdentifier);
		$namespace = Identifier::make(
			$tableIdentifier->getParentIdentifier());

		$indexes = [];
		foreach ($names as $name)
		{
			$identifier = clone $namespace;
			$identifier->append($name);

			$index = new IndexStructure($name);
			$columns = $this->scopedAssetPragmaList('index_info',
				$identifier);
			$index->columns(...$columns);
			$indexes[] = $index;
		}

		return $indexes;
	}

	public function getTableColumnNames($tableIdentifier)
	{
		return $this->scopedAssetPragmaList('table_info',
			$tableIdentifier);
	}

	public function getTableColumn($tableIdentifier, $columnName)
	{
		$recordset = $this->scopedAssetPragma('table_info',
			$tableIdentifier);
		$recordset->setFlags(
			K::RECORDSET_FETCH_ASSOCIATIVE |
			K::RECORDSET_FETCH_UNSERIALIZE);

		$primaryKeyCount = 0;
		$info = Container::filter($recordset,
			function ($index, $row) use ($columnName, &$primaryKeyCount) {
				if ($row['pk'] != 0)
					$primaryKeyCount++;
				return (\strcasecmp($row['name'], $columnName) == 0);
			});

		if (!\count($info))
			throw new StructureExplorerException(
				$columnName . ' not found');

		$platform = $this->getConnection()->getPlatform();

		$info = Container::firstValue($info);

		$isPrimary = ($info['pk'] != 0);
		$typename = $info['type'];
		$defaultValue = $info['dflt_value'];
		$length = 0;
		$scale = 0;
		$flags = 0;
		$dataType = 0;

		if ($info['notnull'] == 0)
			$dataType |= K::DATATYPE_NULL;

		if (empty($typename))
			$typename = 'text';
		if (\preg_match(
			'/^(.+?)\(([1-9][0-9]*)\s*(?:,\s*([0-9]+))?\)$/', $typename,
			$m))
		{
			$typename = Container::keyValue($m, 1, $typename);
			$length = \intval(Container::keyValue($m, 2, 0));
			$scale = \intval(Container::keyValue($m, 3, 0));
		}

		if (\preg_match('/((?:un)?signed)\s*(.*)/i', $typename, $m))
		{
			$typename = Container::keyValue($m, 2, $typename);
			$sign = Container::keyValue($m, 1, '');
			if (\strcasecmp($sign, 'unsigned') == 0)
				$flags |= K::COLUMN_FLAG_UNSIGNED;
		}

		/*
		 * Auto icrement column is assumed if
		 * * type is "integer"
		 * * column is part of the primary key
		 * * primary key contains only one column
		 */

		if ($isPrimary && ($primaryKeyCount == 1) && $length == 0 &&
			$scale == 0 && \strcasecmp($typename, 'integer') == 0)
		{
			if ($this->tableHasAutoIncrementColumn($tableIdentifier))
				$flags |= K::COLUMN_FLAG_AUTO_INCREMENT;
		}

		$registry = $platform->getTypeRegistry();
		$type = null;
		if ($registry->has($typename))
			$type = $platform->getTypeRegistry()->get($typename);
		else
		{
			$type = new ArrayObjectType(
				[
					K::TYPE_NAME => $typename,
					K::TYPE_DATA_TYPE => SQLite3TypeRegistry::getInstance()->getDataTypeFromTypename(
						$typename),
					K::TYPE_FLAGS => K::TYPE_FLAG_LENGTH
				]);
		}

		$dataType |= $type->get(K::TYPE_DATA_TYPE);

		$properties = [
			K::COLUMN_NAME => $columnName,
			K::COLUMN_DATA_TYPE => $dataType
		];
		if ($flags)
			$properties[K::COLUMN_FLAGS] = $flags;
		if ($length)
			$properties[K::COLUMN_LENGTH] = $length;
		if ($scale)
			$properties[K::COLUMN_FRACTION_SCALE] = $scale;
		if ($type->has(K::TYPE_MEDIA_TYPE))
			$properties[K::TYPE_MEDIA_TYPE] = $type->get(
				K::TYPE_MEDIA_TYPE);

		if (!empty($defaultValue))
		{
			$isKeyword = false;

			static $keywords = [
				K::KEYWORD_NULL => [
					Data::class,
					[
						null,
						K::DATATYPE_NULL
					]
				],
				K::KEYWORD_CURRENT_TIMESTAMP => [
					Keyword::class,
					[
						K::KEYWORD_CURRENT_TIMESTAMP
					]
				]
			];
			foreach ($keywords as $k => $c)
			{
				if ($defaultValue != $platform->getKeyword($k))
					continue;
				$isKeyword = true;
				$cls = new \ReflectionClass($c[0]);
				$value = $cls->newInstanceArgs($c[1]);
				$properties[K::COLUMN_DEFAULT_VALUE] = $value;
				break;
			}

			if (!$isKeyword)
			{
				try
				{
					$sql = 'SELECT ' . $defaultValue . " AS value";
					$recordset = $this->getConnection()->executeStatement(
						$sql);

					$recordset->getResultColumns()->offsetSet(0,
						new ArrayColumnDescription(
							[
								K::COLUMN_NAME => 'value',
								K::COLUMN_DATA_TYPE => $dataType &
								~K::DATATYPE_NULL
							]));

					$recordset->setFlags(
						$recordset->getFlags() |
						K::RECORDSET_FETCH_UNSERIALIZE);
					$defaultValue = self::recordsetToValue($recordset,
						'value');
				}
				catch (\Exception $e)
				{}

				$properties[K::COLUMN_DEFAULT_VALUE] = new Data(
					$defaultValue, $dataType & ~K::DATATYPE_NULL);
			}
		}

		return new ArrayColumnDescription($properties);
	}

	public function getViewNames($parentIdentifier = null)
	{
		$parentIdentifier = Identifier::make($parentIdentifier);
		$master = clone $parentIdentifier;
		$master->append('sqlite_master');
		$platform = $this->getConnection()->getPlatform();

		$sql = \sprintf(
			"SELECT name, sql FROM %s " .
			"WHERE type='view' AND sql NOT NULL",
			$platform->quoteIdentifierPath($master));

		$recordset = $this->getConnection()->executeStatement($sql);
		return self::recordsetToList($recordset);
	}

	public function tableHasAutoIncrementColumn($tableIdentifier)
	{
		$tableIdentifier = Identifier::make($tableIdentifier);
		$tableName = $tableIdentifier->getLocalName();

		$namespace = $tableIdentifier->getParentIdentifier();
		$platform = $this->getConnection()->getPlatform();

		// When at least on record was inserted in the table, a sequence was created
		{
			$sequence = [
				'sqlite_sequence'
			];
			if ($namespace)
				array_unshift($sequence, $namespace->getLocalName());

			$sql = \sprintf(
				'SELECT COUNT(*)' . ' FROM %s' . ' WHERE name=%s',
				$platform->quoteIdentifierPath($sequence),
				$platform->quoteIdentifier($tableName));

			try
			{
				$result = $this->getConnection()->executeStatement($sql);
				$result->setFlags(
					K::RECORDSET_FETCH_INDEXED |
					K::RECORDSET_FETCH_UNSERIALIZE);
				if ($result->current()[0] > 0)
					return true;
			}
			catch (ConnectionException $e)
			{}
		}

		// Otherwise, look in SQL instructions
		{
			$master = [
				'sqlite_master'
			];
			if ($namespace)
				\array_unshift($master, $namespace->getLocalName());

			$sql = \sprintf(
				"SELECT sql FROM %s WHERE type='table' and name=%s",
				$platform->quoteIdentifierPath($master),
				$platform->quoteIdentifier($tableName));
			if ($namespace == null)
			{
				$sql .= \sprintf(
					" UNION ALL SELECT sql FROM sqlite_temp_mapster WHERE type='table' and name=%s",
					$platform->quoteIdentifier($tableName));
			}

			$result = $this->getConnection()->executeStatement($sql);
			$row = $result->current();
			$text = $row['sql'];

			$pattern = '(?<column>.+?)' . '\s+integer' .
				'\s+(?:.*?)(?:primary\s+key)' .
				'\s+(?:.*?)autoincrement(?:\s|,|\))';

			if (\preg_match(chr(1) . $pattern . chr(1) . 'i', $text))
				return true;
		}

		return false;
	}

	public function getTableConstraintNames($tableIdentifier, $type)
	{
		$tableSQL = $this->getTableSQL($tableIdentifier);
		$fkNamePattern = '/(?:CONSTRAINT\s+"(?<name>.*?)"\s*)?' . $type .
			'/i';
		$names = [];
		preg_match_all($fkNamePattern, $tableSQL, $names, PREG_SET_ORDER);

		return Container::map($names,
			function ($k, $v) {
				return Container::keyValue($v, 'name');
			});
	}

	/**
	 *
	 * @param Identifier $tableIdentifier
	 */
	public function getTableSQL($tableIdentifier)
	{
		$tableIdentifier = Identifier::make($tableIdentifier);
		$namespace = Identifier::make(
			$tableIdentifier->getParentIdentifier());

		$master = clone $namespace;
		$master->append('sqlite_master');

		$platform = $this->getConnection()->getPlatform();

		$sql = sprintf('SELECT sql FROM %s WHERE name=%s',
			$platform->quoteIdentifierPath($master),
			$platform->quoteStringValue(
				$tableIdentifier->getLocalName()));
		$recordset = $this->getConnection()->executeStatement($sql);
		return self::recordsetToValue($recordset, 'sql');
	}

	/**
	 *
	 * @param string $pragmaName
	 * @param string $assetIdentifier
	 * @return Recordset
	 */
	protected function scopedAssetPragma($pragmaName, $assetIdentifier)
	{
		$assetIdentifier = Identifier::make($assetIdentifier);
		$namespace = $assetIdentifier->getParentIdentifier();

		$platform = $this->getConnection()->getPlatform();

		$sql = \sprintf('PRAGMA %s%s(%s)',
			($namespace ? $platform->quoteIdentifierPath($namespace) .
			'.' : ''), $pragmaName,
			$platform->quoteIdentifier($assetIdentifier->getLocalName()));

		return $this->getConnection()->executeStatement($sql);
	}

	protected function scopedAssetPragmaList($pragmaName,
		$assetIdentifier, $columnName = 'name')
	{
		$recordset = $this->scopedAssetPragma($pragmaName,
			$assetIdentifier);
		return self::recordsetToList($recordset, $columnName);
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
