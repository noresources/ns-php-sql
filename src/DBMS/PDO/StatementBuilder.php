<?php

// NAmespace
namespace NoreSources\SQL\DBMS\PDO;

// Aliases
use NoreSources\SQL;
use NoreSources\SQL\Constants as K;

class StatementBuilder extends SQL\Statement\Builder
{

	const DRIVER_MYSQL = Connection::DRIVER_MYSQL;

	const DRIVER_POSTGRESQL = Connection::DRIVER_POSTGRESQL;

	const DRIVER_SQLITE = Connection::DRIVER_SQLITE;

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

	public function getParameter($name, $position)
	{
		return (':' . $position);
	}

	public function getColumnTypeName(SQL\TableColumnStructure $column)
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