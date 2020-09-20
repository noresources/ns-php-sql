<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\PDO;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\BasicType;
use NoreSources\SQL\DBMS\PlatformProviderTrait;
use NoreSources\SQL\Statement\AbstractStatementBuilder;
use NoreSources\SQL\Statement\ClassMapStatementFactoryTrait;
use NoreSources\SQL\Statement\ParameterData;
use NoreSources\SQL\Structure\ColumnStructure;

class PDOStatementBuilder extends AbstractStatementBuilder
{

	use ClassMapStatementFactoryTrait;
	use PlatformProviderTrait;

	const DRIVER_MYSQL = PDOConnection::DRIVER_MYSQL;

	const DRIVER_POSTGRESQL = PDOConnection::DRIVER_POSTGRESQL;

	const DRIVER_SQLITE = PDOConnection::DRIVER_SQLITE;

	public function __construct(PDOConnection $connection)
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
		$o = null;
		if ($this->connection instanceof PDOConnection)
			$o = $this->connection->getConnectionObject();

		if ($o instanceof \PDO)
			return $o->quote($value);

		return "'" . self::escapeString($value) . "'";
	}

	public function escapeIdentifier($identifier)
	{
		switch ($this->driverName)
		{
			case self::DRIVER_POSTGRESQL:
				return '"' . $identifier . '"';
			case self::DRIVER_MYSQL:
				return '`' . $identifier . '`';
			case self::DRIVER_SQLITE:
				return '[' . $identifier . ']';
		}
		return $identifier;
	}

	public function configure(\PDO $connection)
	{
		$this->driverName = $connection->getAttribute(
			\PDO::ATTR_DRIVER_NAME);
	}

	public function getParameter($name, ParameterData $parameters = null)
	{
		return (':' . $parameters->count());
	}

	public function getColumnType(ColumnStructure $column)
	{
		$dataType = K::DATATYPE_UNDEFINED;
		if ($column->hasColumnProperty(K::COLUMN_DATA_TYPE))
			$dataType = $column->getColumnProperty(K::COLUMN_DATA_TYPE);

		$typeName = 'TEXT';

		switch ($dataType)
		{
			case K::DATATYPE_BINARY:
				$typeName = 'BLOB';
			break;
			case K::DATATYPE_NUMBER:
			case K::DATATYPE_FLOAT:
				$typeName = 'REAL';
			break;
			case K::DATATYPE_BOOLEAN:
			case K::DATATYPE_INTEGER:
				$typeName = 'INTEGER';
			break;
			case K::DATATYPE_NULL:
				$typeName = 'NULL';
			break;
		}

		return new BasicType($typeName);
	}

	private $driverName;

	/**
	 *
	 * @var PDOConnection $connection
	 */
	private $connection;
}