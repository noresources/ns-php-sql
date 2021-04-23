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
use NoreSources\SQL\Structure\ArrayColumnDescription;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Syntax\Data;

/**
 * StructureExplorer implementation that use the ANSI information_schema
 */
trait InformationSchemaStructureExplorerTrait
{

	public function getInformationSchemaTableNames(
		ConnectionInterface $connection,
		Identifier $namespace)
	{
		$platform = $connection->getPlatform();

		$sql = \sprintf(
			'SELECT table_name
			FROM information_schema.tables
			WHERE
				table_type != %s
				AND table_schema = %s', $platform->quoteStringValue('VIEW'),
			$platform->quoteStringValue($namespace));

		$recordset = $connection->executeStatement($sql);
		return self::recordsetToList($recordset, 'table_name');
	}

	public function getInformationSchemaTableColumnNames(
		ConnectionInterface $connection,
		Identifier $qualifiedTableIdentifier)
	{
		$tableName = $qualifiedTableIdentifier->getLocalName();
		$namespace = $qualifiedTableIdentifier->getParentIdentifier();
		$platform = $connection->getPlatform();

		$sql = sprintf(
			'SELECT column_name
			FROM information_schema.columns
			WHERE table_schema=%s
				AND table_name=%s
			ORDER BY ordinal_position
', $platform->quoteStringValue($namespace),
			$platform->quoteStringValue($tableName));

		return self::recordsetToList(
			$connection->executeStatement($sql), 0);
	}

	public function getInformationSchemaTableColumn(
		ConnectionInterface $connection,
		Identifier $qualifiedTableIdentifier,
		$columnName, $extraColumns = array())
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

		$informationSchemaColumnsString = Container::implode(
			$informationSchemaColumns, ', ',
			function ($name, $alias) use ($platform) {
				if (\is_integer($name))
					return $platform->quoteIdentifier($alias);
				return $platform->quoteIdentifier($name) . ' AS ' .
				$platform->quoteIdentifier($alias);
			});

		$sql = sprintf(
			'SELECT %s
			FROM information_schema.columns
			WHERE table_schema=%s
				AND table_name=%s
				AND column_name=%s', $informationSchemaColumnsString,
			$platform->quoteStringValue($namespace),
			$platform->quoteStringValue($tableName),
			$platform->quoteStringValue($columnName));

		$recordset = $connection->executeStatement($sql);
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

		$recordset = $connection->executeStatement($sql);
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

		$sql = sprintf(
			'SELECT
					table_name
				FROM information_schema.views
				WHERE table_schema=%s
', $platform->quoteStringValue($namespace));

		return self::recordsetToList(
			$connection->executeStatement($sql), 0);
	}

	public function populateInformationSchemaForeignKeyActions(
		ForeignKeyTableConstraint &$foreignKey,
		ConnectionInterface $connection,
		Identifier $namespace, $actionMap = null)
	{
		if ($actionMap === null)
			$actionMap = [
				'CASCADE' => K::FOREIGN_KEY_ACTION_CASCADE,
				'RESTRICT' => K::FOREIGN_KEY_ACTION_RESTRICT,
				'SET DEFAULT' => K::FOREIGN_KEY_ACTION_SET_DEFAULT,
				'SET NULL' => K::FOREIGN_KEY_ACTION_SET_NULL
			];
		$platform = $connection->getPlatform();
		$sql = sprintf(
			"SELECT
					update_rule,
					delete_rule
				FROM information_schema.referential_constraints
					WHERE constraint_schema=%s
					AND constraint_name=%s", $platform->quoteStringValue($namespace),
			$platform->quoteStringValue($foreignKey->getName()));

		$rules = $connection->executeStatement($sql);
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
