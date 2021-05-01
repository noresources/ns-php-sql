<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Environment;
use NoreSources\SQL\Structure\ArrayColumnDescription;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Syntax\Data;
use NoreSources\SQL\Syntax\Statement\Query\SelectQuery;

/**
 * StructureExplorer implementation that use the ANSI information_schema
 */
trait InformationSchemaStructureExplorerTrait
{

	public function getInformationSchemaTableNames(
		ConnectionInterface $connection, Identifier $namespace)
	{
		$platform = $connection->getPlatform();
		/**
		 *
		 * @var SelectQuery $query
		 */
		$query = $platform->newStatement(K::QUERY_SELECT);
		$query->columns('table_name')
			->from('information_schema.tables')
			->where([
			'table_schema' => new Data($namespace)
		], [
			'<>' => [
				'table_type',
				new Data('VIEW')
			]
		]);

		$env = new Environment($connection);
		$recordset = $env->executeStatement($query);
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
		$query = $platform->newStatement(K::QUERY_SELECT);

		$query->columns('column_name')
			->from('information_schema.columns')
			->where([
			'table_schema' => new Data($namespace)
		], [
			'table_name' => new Data($tableName)
		])
			->orderBy('ordinal_position');
		$env = new Environment($connection);
		return self::recordsetToList($env->executeStatement($query), 0);
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
		$query = $platform->newStatement(K::QUERY_SELECT);

		$query->columns(...$columns)
			->from('information_schema.columns')
			->where([
			'table_schema' => new Data($namespace)
		], [
			'table_name' => new Data($tableName)
		], [
			'column_name' => new Data($columnName)
		]);
		$env = new Environment($connection);

		$recordset = $env->executeStatement($query);
		$recordset->setFlags(K::RECORDSET_FETCH_ASSOCIATIVE);
		$info = $recordset->current();
		$properties = [];

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
			K::COLUMN_NAME => $columnName,
			K::COLUMN_DATA_TYPE => $dataType
		];

		if ($flags)
			$properties[K::COLUMN_FLAGS] = $flags;

		if (!empty($info['column_default']))
		{
			$this->processInformationSchemaColumnDefault($properties,
				$info['column_default']);
		}

		$this->processInformationSchemaColumnInformations($properties,
			$info);

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

	public function getInformationSchemaTablePrimaryKeyConstraint(
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
		$query = $platform->newStatement(K::QUERY_SELECT);
		$query->columns([
			'tc.constraint_name' => 'name'
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
			'tc.constraint_type' => new Data('PRIMARY KEY')
		], [
			'tc.table_schema' => new Data($namespace)
		], [
			'tc.table_name' => new Data($tableName)
		]);

		$env = new Environment($connection);
		$recordset = $env->executeStatement($query);

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
		$query = $platform->newStatement(K::QUERY_SELECT);

		$query->columns('table_name')
			->from('information_schema.views')
			->where([
			'table_schema' => new Data($namespace)
		]);

		$env = new Environment($connection);
		$recordset = $env->executeStatement($query);
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
		$query = $platform->newStatement(K::QUERY_SELECT);
		$query->columns('update_rule', 'delete_rule')
			->from('information_schema.referential_constraints')
			->where([
			'constraint_schema' => new Data($namespace)
		], [
			'constraint_name' => new Data($foreignKey->getName())
		]);

		$env = new Environment($connection);
		$rules = $env->executeStatement($query);

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
