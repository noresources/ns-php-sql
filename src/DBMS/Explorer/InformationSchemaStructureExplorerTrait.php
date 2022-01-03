<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Explorer;

use NoreSources\Container\Container;
use NoreSources\Container\KeyNotFoundException;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\TypeInterface;
use NoreSources\SQL\Structure\ArrayColumnDescription;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\KeyTableConstraintInterface;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\UniqueTableConstraint;
use NoreSources\SQL\Syntax\Data;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\SQL\Syntax\Statement\Query\SelectQuery;

/**
 * StructureExplorer implementation that use the ANSI information_schema
 */
trait InformationSchemaStructureExplorerTrait
{

	public function getInformationSchemaTableNames(
		ConnectionInterface $connection, Identifier $namespace,
		$tableType = 'BASE TABLE')
	{
		$platform = $connection->getPlatform();
		/**
		 *
		 * @var SelectQuery $query
		 */
		$query = $platform->newStatement(SelectQuery::class);
		$query->columns('table_name')
			->from('information_schema.tables')
			->where([
			'table_schema' => new Data($namespace)
		], [
			'=' => [
				'table_type',
				new Data($tableType)
			]
		]);

		$query = StatementBuilder::getInstance()->build($query,
			$platform);
		$recordset = $connection->executeStatement($query);
		return self::recordsetToList($recordset, 'table_name');
	}

	public function getInformationSchemaTableColumnNames(
		ConnectionInterface $connection,
		Identifier $qualifiedTableIdentifier)
	{
		$tableName = $qualifiedTableIdentifier->getLocalName();
		$namespace = $qualifiedTableIdentifier->getParentIdentifier();
		$platform = $connection->getPlatform();

		/**
		 *
		 * @var SelectQuery $query
		 */
		$query = $platform->newStatement(SelectQuery::class);

		$query->columns('column_name')
			->from('information_schema.columns')
			->where([
			'table_schema' => new Data($namespace)
		], [
			'table_name' => new Data($tableName)
		])
			->orderBy('ordinal_position');

		$query = StatementBuilder::getInstance()->build($query,
			$platform);

		return self::recordsetToList(
			$connection->executeStatement($query), 0);
	}

	public function getInformationSchemaTableColumn(
		ConnectionInterface $connection,
		Identifier $qualifiedTableIdentifier, $columnName,
		$extraColumns = array())
	{
		$tableName = $qualifiedTableIdentifier->getLocalName();
		$namespace = $qualifiedTableIdentifier->getParentIdentifier();
		$platform = $connection->getPlatform();

		$informationSchemaColumns = \array_merge(
			[
				'column_default',
				'is_nullable',
				'data_type',
				'character_maximum_length',
				'numeric_precision',
				'numeric_scale'
			], $extraColumns);

		$columns = [];
		foreach ($informationSchemaColumns as $c => $a)
		{
			if (\is_integer($c))
				$columns[] = $a;
			else
				$columns[] = [
					$c => $a
				];
		}

		/**
		 *
		 * @var SelectQuery $query
		 */
		$query = $platform->newStatement(SelectQuery::class);

		$query->columns(...$columns)
			->from('information_schema.columns')
			->where([
			'table_schema' => new Data($namespace)
		], [
			'table_name' => new Data($tableName)
		], [
			'column_name' => new Data($columnName)
		])
			->orderBy('ordinal_position');

		$query = StatementBuilder::getInstance()->build($query,
			$platform);

		$recordset = $connection->executeStatement($query);
		$recordset->setFlags(K::RECORDSET_FETCH_ASSOCIATIVE);
		$info = $recordset->current();
		$properties = [];

		$dataType = 0;
		$dbmsType = null;
		try
		{
			$dbmsType = $platform->getTypeRegistry()->get(
				$info['data_type']);
			$dataType = $dbmsType->get(K::TYPE_DATA_TYPE);
		}
		catch (KeyNotFoundException $e)
		{
			if (false)
				trigger_error(
					'Type ' . $info["data_type"] .
					' not found in type registry', E_WARNING);
		/**
		 *
		 * @todo warning
		 */
		}
		$flags = 0;
		if (\strcasecmp($info['is_nullable'], 'yes') == 0)
			$dataType |= K::DATATYPE_NULL;

		if ($dataType & K::DATATYPE_STRING)
		{
			if (!empty($info['character_maximum_length']))
			{
				$properties[K::COLUMN_LENGTH] = \intval(
					$info['character_maximum_length']);
			}
		}

		if ($dataType & K::DATATYPE_NUMBER)
		{
			$precision = 0;
			$scale = 0;
			if (!empty($info['numeric_precision']))
				$precision = \intval($info['numeric_precision']);

			if (!empty($info['numeric_scale']))
				$scale = \intval($info['numeric_scale']);

			if ($precision)
				$properties[K::COLUMN_PRECISION] = $precision;
			if ($scale)
				$properties[K::COLUMN_FRACTION_SCALE] = $scale;
		}

		$properties[K::COLUMN_NAME] = $columnName;
		$properties[K::COLUMN_DATA_TYPE] = $dataType;

		if ($flags)
			$properties[K::COLUMN_FLAGS] = $flags;

		if (!empty($info['column_default']))
		{
			$this->processInformationSchemaColumnDefault($properties,
				$info['column_default']);
		}

		$this->processInformationSchemaColumnInformations($properties,
			$info);

		// Cleanup some properties
		if ($dbmsType instanceof TypeInterface)
		{
			$typeFlags = Container::keyValue($dbmsType, K::TYPE_FLAGS);
			if (($typeFlags & K::TYPE_FLAG_FRACTION_SCALE) !=
				K::TYPE_FLAG_FRACTION_SCALE)
				Container::removeKey($properties,
					K::COLUMN_FRACTION_SCALE);

			if (($typeFlags & K::TYPE_FLAG_LENGTH) != K::TYPE_FLAG_LENGTH)
				Container::removeKey($properties, K::COLUMN_LENGTH);
		}

		return new ArrayColumnDescription($properties);
	}

	/**
	 *
	 * @param array $properties
	 * @param array $info
	 *        	Column info record
	 */
	protected function processInformationSchemaColumnInformations(
		&$properties, $info)
	{}

	/**
	 *
	 * @param array $properties
	 * @param array $columnValue
	 */
	protected function processInformationSchemaColumnDefault(
		&$properties, $columnValue)
	{
		$dataType = Container::keyValue($properties, K::COLUMN_DATA_TYPE,
			K::DATATYPE_UNDEFINED);
		$properties[K::COLUMN_DEFAULT_VALUE] = new Data($columnValue,
			$dataType);
	}

	/**
	 *
	 * @param ConnectionInterface $connection
	 * @param Identifier $qualifiedTableIdentifier
	 * @param string $type
	 *        	UNIQUE or PRIMARY KEY
	 * @return \NoreSources\SQL\Structure\KeyTableConstraintInterface[]
	 */
	public function getInformationSchemaTableKeyConstraints(
		ConnectionInterface $connection,
		Identifier $qualifiedTableIdentifier, $type = null,
		$asRecordset = false)
	{
		$tableName = $qualifiedTableIdentifier->getLocalName();
		$namespace = $qualifiedTableIdentifier->getParentIdentifier();
		$platform = $connection->getPlatform();

		/**
		 *
		 * @var SelectQuery $query
		 */
		$query = $platform->newStatement(SelectQuery::class);
		$query->columns([
			'tc.constraint_name' => 'name'
		], [
			'tc.constraint_type' => 'type'
		], [
			'kcu.column_name' => 'column'
		])
			->from('information_schema.table_constraints', 'tc')
			->join(K::JOIN_INNER,
			[
				'information_schema.key_column_usage' => 'kcu'
			], [
				'tc.constraint_name' => 'kcu.constraint_name'
			], [
				'tc.table_schema' => 'kcu.table_schema'
			])
			->where([
			'tc.table_schema' => new Data($namespace)
		], [
			'tc.table_name' => new Data($tableName)
		])
			->orderBy('name')
			->orderBy('kcu.ordinal_position');

		if (isset($type))
		{
			$query->where([
				'tc.constraint_type' => new Data($type)
			]);
		}
		else
			$query->where(
				[
					'in' => [
						'tc.constraint_type',
						[
							new Data('UNIQUE'),
							new Data('PRIMARY KEY')
						]
					]
				]);

		$query = StatementBuilder::getInstance()->build($query,
			$platform);
		$recordset = $connection->executeStatement($query);

		if ($asRecordset)
			return $recordset;

		$recordset->setFlags(
			K::RECORDSET_FETCH_ASSOCIATIVE |
			K::RECORDSET_FETCH_UNSERIALIZE);

		/**  @var KeyTableConstraintInterface */

		$key = null;
		$keys = [];

		foreach ($recordset as $row)
		{
			$name = $row['name'];

			$column = $row['column'];

			if (!isset($key) || ($key->getName() != $name))
			{
				$type = $row['type'];
				if ($type == 'PRIMARY KEY')
					$key = new PrimaryKeyTableConstraint();
				else
					$key = new UniqueTableConstraint();
				$key->setName($name);
				$keys[] = $key;
			}

			$key->append($column);
		}

		return $keys;
	}

	public function getInformationSchemaViewNames(
		ConnectionInterface $connection,
		Identifier $parentIdentifier = null)
	{
		$namespace = $parentIdentifier->getPath();
		$platform = $connection->getPlatform();

		/**
		 *
		 * @var SelectQuery $query
		 */
		$query = $platform->newStatement(SelectQuery::class);

		$query->columns('table_name')
			->from('information_schema.views')
			->where([
			'table_schema' => new Data($namespace)
		]);

		$query = StatementBuilder::getInstance()->build($query,
			$platform);
		$recordset = $connection->executeStatement($query);
		return self::recordsetToList($recordset, 0);
	}

	public function populateInformationSchemaForeignKeyActions(
		ForeignKeyTableConstraint &$foreignKey,
		ConnectionInterface $connection, Identifier $namespace,
		$actionMap = null)
	{
		if ($actionMap === null)
			$actionMap = [
				'CASCADE' => K::FOREIGN_KEY_ACTION_CASCADE,
				'RESTRICT' => K::FOREIGN_KEY_ACTION_RESTRICT,
				'SET DEFAULT' => K::FOREIGN_KEY_ACTION_SET_DEFAULT,
				'SET NULL' => K::FOREIGN_KEY_ACTION_SET_NULL
			];
		$platform = $connection->getPlatform();

		/**
		 *
		 * @var SelectQuery $query
		 */
		$query = $platform->newStatement(SelectQuery::class);
		$query->columns('update_rule', 'delete_rule')
			->from('information_schema.referential_constraints')
			->where([
			'constraint_schema' => new Data($namespace)
		], [
			'constraint_name' => new Data($foreignKey->getName())
		]);

		$query = StatementBuilder::getInstance()->build($query,
			$platform);
		$rules = $connection->executeStatement($query);

		if (($rules = $rules->current()))
		{
			if ($action = Container::keyValue($actionMap,
				$rules['update_rule']))
				$foreignKey->getEvents()->on(K::EVENT_UPDATE, $action);
			if ($action = Container::keyValue($actionMap,
				$rules['delete_rule']))
				$foreignKey->getEvents()->on(K::EVENT_DELETE, $action);
		}
	}
}
