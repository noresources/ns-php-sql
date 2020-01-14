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

// Aliases
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Statement\ParameterMap;
use NoreSources\SQL\Statement\StatementBuilder;
use NoreSources\SQL\Structure\ColumnStructure;

class PDOStatementBuilder extends StatementBuilder
{

	const DRIVER_MYSQL = PDOConnection::DRIVER_MYSQL;

	const DRIVER_POSTGRESQL = PDOConnection::DRIVER_POSTGRESQL;

	const DRIVER_SQLITE = PDOConnection::DRIVER_SQLITE;

	public function __construct()
	{
		parent::__construct();
	}

	public function escapeString($value)
	{
		return \PDO::quote($value);
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
		$this->driverName = $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
	}

	public function getParameter($name, ParameterMap $parameters = null)
	{
		return (':' . $parameters->count());
	}

	public function getColumnTypeName(ColumnStructure $column)
	{
		$dataType = K::DATATYPE_UNDEFINED;
		if ($column->hasColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE))
			$dataType = $column->getColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE);

		switch ($dataType)
		{
			case K::DATATYPE_BINARY:
				return 'BLOB';
			case K::DATATYPE_NUMBER:
			case K::DATATYPE_FLOAT:
				return 'REAL';
			case K::DATATYPE_BOOLEAN:
			case K::DATATYPE_INTEGER:
				return 'INTEGER';
			case K::DATATYPE_NULL:
				return NULL;
		}

		return 'TEXT';
	}

	public function getKeyword($keyword)
	{
		return parent::getKeyword($keyword);
	}

	private $driverName;
}