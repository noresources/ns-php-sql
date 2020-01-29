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

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Reference\ReferenceStatementBuilder;
use NoreSources\SQL\Statement\ParameterMap;
use NoreSources\SQL\Statement\StatementBuilder;
use NoreSources\SQL\Structure\ColumnStructure;

// Aliases
class PostgreSQLStatementBuilder extends StatementBuilder
{

	public function __construct(PostgreSQLConnection $connection = null)
	{
		parent::__construct();
		$this->connection = $connection;
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
		return "E'" . \pg_escape_bytea($value) . "'";
	}

	public function escapeIdentifier($identifier)
	{
		$resource = $this->getConnectionResource();
		$result = false;
		if (\is_resource($resource))
			$result = \pg_escape_identifier($resource, $identifier);

		if ($result !== false)
			return $result;

		return ReferenceStatementBuilder::escapeIdentifierFallback($identifier, '"', '"');
	}

	public function getParameter($name, ParameterMap $parameters = null)
	{
		$name = strval($name);
		if ($parameters->offsetExists($name))
		{
			return $parameters->offsetGet($name);
		}

		return '$' . ($parameters->getNamedParameterCount() + 1);
	}

	public static function getPostgreSQLColumnTypeName(ColumnStructure $column)
	{
		$dataType = K::DATATYPE_UNDEFINED;
		if ($column->hasColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE))
			$dataType = $column->getColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE);

		if ($dataType & K::DATATYPE_TIMESTAMP)
		{
			if (($dataType & K::DATATYPE_TIME) == K::DATATYPE_TIME)
			{
				return 'time' . ((($dataType & K::DATATYPE_DATE) == K::DATATYPE_DATE) ? 'stamp' : '') .
					' with' .
					((($dataType & K::DATATYPE_TIMEZONE) == K::DATATYPE_TIMEZONE) ? '' : 'out') .
					' time zone';
			}

			return 'date';
		}

		if ($dataType & K::DATATYPE_NUMBER)
		{
			if (($dataType & K::DATATYPE_NUMBER) == K::DATATYPE_INTEGER)
			{
				if ($column->hasColumnProperty(K::COLUMN_PROPERTY_AUTO_INCREMENT))
				{
					if ($column->getColumnProperty(K::COLUMN_PROPERTY_AUTO_INCREMENT))
						return 'serial';
					return 'integer';
				}
			}

			return 'real';
		}

		if ($dataType == K::DATATYPE_BINARY)
			return 'bytea';
		elseif ($dataType == K::DATATYPE_BOOLEAN)
			return 'boolean';

		return 'TEXT';
	}

	public function getColumnTypeName(ColumnStructure $column)
	{
		return self::getPostgreSQLColumnTypeName($column);
	}

	public function getKeyword($keyword)
	{
		switch ($keyword)
		{
			case K::KEYWORD_AUTOINCREMENT:
				return '';
		}
		return parent::getKeyword($keyword);
	}

	private function getConnectionResource()
	{
		if ($this->connection instanceof PostgreSQLConnection)
		{
			if (\is_readable($this->connection->getConnectionResource()))
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