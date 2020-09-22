<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\TypeConversion;
use NoreSources\SQL\DBMS\MySQL\MySQLConstants as K;
use NoreSources\SQL\Statement\AbstractStatementBuilder;
use NoreSources\SQL\Statement\ClassMapStatementFactoryTrait;
use NoreSources\SQL\Statement\ParameterData;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class MySQLStatementBuilder extends AbstractStatementBuilder implements
	LoggerAwareInterface
{

	use LoggerAwareTrait;
	use ClassMapStatementFactoryTrait;

	public function __construct(MySQLConnection $connection)
	{
		parent::__construct();
		$this->initializeStatementFactory();
		$this->connection = $connection;
	}

	/**
	 *
	 * @return MySQLPlatform
	 */
	public function getPlatform()
	{
		return $this->connection->getPlatform();
	}

	public function serializeString($value)
	{
		if ($this->connection->isConnected())
			return "'" . $this->getLink()->real_escape_string($value) .
				"'";

		return "'" . self::escapeString($value) . "'";
	}

	public function serializeBinary($value)
	{
		if (\is_integer($value) || \is_float($value) || \is_null($value))
			return $value;

		if ($value instanceof \DateTimeInterface)
			$value = $value->format(
				$this->getPlatform()
					->getTimestampTypeStringFormat(
					K::DATATYPE_TIMESTAMP));
		else
			$value = TypeConversion::toString($value);

		return $this->serializeString($value);
	}

	public function escapeIdentifier($identifier)
	{
		return '`' . $identifier . '`';
	}

	public function getParameter($name, ParameterData $parameters = null)
	{
		return '?';
	}

	public static function dataTypeFromMysqlType($mysqlTypeId)
	{
		switch ($mysqlTypeId)
		{
			case MYSQLI_TYPE_DECIMAL:
			case MYSQLI_TYPE_NEWDECIMAL:
			case MYSQLI_TYPE_FLOAT:
			case MYSQLI_TYPE_DOUBLE:
				return K::DATATYPE_FLOAT;

			case MYSQLI_TYPE_BIT:
				return K::DATATYPE_BOOLE;

			case MYSQLI_TYPE_TINY:
			case MYSQLI_TYPE_SHORT:
			case MYSQLI_TYPE_LONG:
			case MYSQLI_TYPE_LONGLONG:
			case MYSQLI_TYPE_INT24:
				return K::DATATYPE_INTEGER;

			case MYSQLI_TYPE_NULL:
				return K::DATATYPE_NULL;

			case MYSQLI_TYPE_TIMESTAMP:
				return K::DATATYPE_TIMESTAMP;

			case MYSQLI_TYPE_DATE:
			case MYSQLI_TYPE_NEWDATE:
			case MYSQLI_TYPE_YEAR:
				return K::DATATYPE_DATE;

			case MYSQLI_TYPE_TIME:
				return K::DATATYPE_TIME;

			case MYSQLI_TYPE_DATETIME:
				return K::DATATYPE_DATETIME;

			case MYSQLI_TYPE_TINY_BLOB:
			case MYSQLI_TYPE_MEDIUM_BLOB:
			case MYSQLI_TYPE_LONG_BLOB:
			case MYSQLI_TYPE_BLOB:
				return K::DATATYPE_BINARY;
		}

		return K::DATATYPE_STRING;
	}

	protected function getLink()
	{
		return $this->connection->getServerLink();
	}

	/**
	 *
	 * @var MySQLConnection
	 */
	private $connection;
}