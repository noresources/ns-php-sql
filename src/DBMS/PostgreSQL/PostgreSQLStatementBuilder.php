<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\PostgreSQL;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\BasicType;
use NoreSources\SQL\DBMS\TypeHelper;
use NoreSources\SQL\DBMS\Reference\ReferenceStatementBuilder;
use NoreSources\SQL\Statement\AbstractStatementBuilder;
use NoreSources\SQL\Statement\ClassMapStatementFactoryTrait;
use NoreSources\SQL\Statement\ParameterData;
use NoreSources\SQL\Structure\ColumnStructure;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class PostgreSQLStatementBuilder extends AbstractStatementBuilder implements
	LoggerAwareInterface
{

	use LoggerAwareTrait;
	use ClassMapStatementFactoryTrait;

	public function __construct(PostgreSQLConnection $connection = null)
	{
		parent::__construct();
		$this->initializeStatementFactory();
		$this->connection = $connection;
	}

	public function getPlatform()
	{
		return $this->connection->getPlatform();
	}

	public function serializeString($value)
	{
		$resource = $this->getConnectionResource();
		$result = false;
		if (\is_resource($resource))
			$result = @\pg_escape_literal($resource, $value);

		if ($result !== false)
			return $result;

		return "'" . \pg_escape_string($value) . "'";
	}

	public function serializeBinary($value)
	{
		if (\is_int($value))
		{
			$value = \base_convert($value, 10, 16);
			if (\strlen($value) % 2 == 1)
			{
				$value = '0' . $value;
			}

			$value = \hex2bin($value);
		}

		return "'" . \pg_escape_bytea($value) . "'";
	}

	public function escapeIdentifier($identifier)
	{
		$resource = $this->getConnectionResource();
		$result = false;
		if (\is_resource($resource))
			$result = \pg_escape_identifier($resource, $identifier);

		if ($result !== false)
			return $result;

		return ReferenceStatementBuilder::escapeIdentifierFallback(
			$identifier, '"', '"');
	}

	public function getParameter($name, ParameterData $parameters = null)
	{
		$key = strval($name);

		if (false)
		{
			/**
			 * Cannot re-use the same parameter number because it may
			 * produce "inconsistent types deduced for parameter"
			 */

			if ($parameters->has($key))
				return $parameters->get($key)[ParameterData::DBMSNAME];

			return '$' . ($parameters->getDistinctParameterCount() + 1);
		}

		return '$' . ($parameters->getParameterCount() + 1);
	}

	public function getColumnType(ColumnStructure $column)
	{
		$columnFlags = $column->getColumnProperty(K::COLUMN_FLAGS);

		// Special case for auto-increment column
		if ($columnFlags & K::COLUMN_FLAG_AUTO_INCREMENT)
		{
			return new BasicType('serial');
		}

		$types = PostgreSQLType::getPostgreSQLTypes();
		$matchingTypes = TypeHelper::getMatchingTypes($column, $types);

		list ($k, $type) = Container::first($matchingTypes);
		return $type;
	}

	public function getConnectionResource()
	{
		if ($this->connection instanceof PostgreSQLConnection)
		{
			if (\is_resource($this->connection->getConnectionResource()))
				return $this->connection->getConnectionResource();
		}

		return null;
	}

	/**
	 *
	 * @var PostgreSQLConnection
	 */
	private $connection;
}