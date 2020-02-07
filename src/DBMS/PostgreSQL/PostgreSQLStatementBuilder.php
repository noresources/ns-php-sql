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

use NoreSources\SemanticVersion;
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

		$this->setBuilderFlags(K::BUILDER_DOMAIN_INSERT,
			K::BUILDER_INSERT_DEFAULT_VALUES | K::BUILDER_INSERT_DEFAULT_KEYWORD);
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

	public function getColumnType(ColumnStructure $column)
	{
		return PostgreSQLType::columnPropertyToType($column);
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
	 * Update builder flags according PostgreSQL server version
	 *
	 * @param SemanticVersion $serverVersion
	 *        	PostgreSQL server version
	 */
	public function updateBuilderFlags(SemanticVersion $serverVersion)
	{
		$createTableFlags = $this->getBuilderFlags(K::BUILDER_DOMAIN_CREATE_TABLE);
		$createTableFlags &= ~(K::BUILDER_IF_NOT_EXISTS);
		$createTablesetFlags = $this->getBuilderFlags(K::BUILDER_DOMAIN_CREATE_TABLESET);
		$createTablesetFlags &= ~K::BUILDER_IF_NOT_EXISTS;

		if (SemanticVersion::compareVersions($serverVersion, '9.1.0') >= 0)
		{
			$createTableFlags |= K::BUILDER_IF_NOT_EXISTS;
		}

		if (SemanticVersion::compareVersions($serverVersion, '9.3.0') >= 0)
		{
			$createTablesetFlags |= K::BUILDER_IF_NOT_EXISTS;
		}

		$this->setBuilderFlags(K::BUILDER_DOMAIN_CREATE_TABLE, $createTableFlags);
		$this->setBuilderFlags(K::BUILDER_DOMAIN_CREATE_TABLESET, $createTablesetFlags);
	}

	/**
	 *
	 * @var PostgreSQLConnection
	 */
	private $connection;
}