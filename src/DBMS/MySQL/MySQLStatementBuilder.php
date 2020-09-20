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

use NoreSources\Container;
use NoreSources\TypeConversion;
use NoreSources\SQL\DBMS\ArrayObjectType;
use NoreSources\SQL\DBMS\BasicType;
use NoreSources\SQL\DBMS\TypeHelper;
use NoreSources\SQL\DBMS\TypeInterface;
use NoreSources\SQL\DBMS\MySQL\MySQLConstants as K;
use NoreSources\SQL\Statement\AbstractStatementBuilder;
use NoreSources\SQL\Statement\ClassMapStatementFactoryTrait;
use NoreSources\SQL\Statement\ParameterData;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\TableStructure;
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

	public function getColumnType(ColumnStructure $column)
	{
		if ($column->hasColumnProperty(K::COLUMN_ENUMERATION))
		{
			return new BasicType('ENUM');
		}

		$types = MySQLType::getMySQLTypes();
		$table = $column->getParentElement();
		$isPrimaryKey = false;

		if ($table instanceof TableStructure)
		{
			$pk = null;
			foreach ($table->getConstraints() as $contraint)
			{
				if ($contraint instanceof PrimaryKeyTableConstraint)
				{
					$pk = $contraint;
					break;
				}
			}

			if ($pk instanceof PrimaryKeyTableConstraint &&
				Container::keyExists($pk->getColumns(),
					$column->getName()))
			{
				$isPrimaryKey = true;
				// Types must have a key length
				$types = Container::filter($types,
					function ($_, $type) {
						/**
						 *
						 * @var TypeInterface $type
						 */

						if ((TypeHelper::getProperty($type,
							K::TYPE_FLAGS) & K::TYPE_FLAG_LENGTH) ==
						K::TYPE_FLAG_LENGTH)
						{
							$maxLength = TypeHelper::getMaxLength($type);
							return !\is_infinite($maxLength);
						}
						return false;
					});
			}
		}

		$types = TypeHelper::getMatchingTypes($column, $types);

		if (Container::count($types) > 0)
		{
			list ($key, $type) = Container::first($types);
			/**
			 *
			 * @var ArrayObjectType $type
			 */

			if ($isPrimaryKey)
			{

				/**
				 * Use active character set maxlen instead
				 *
				 * @var integer $glyphLength
				 */
				$glyphLength = 4;
				$keyMaxLength = intval(
					floor(K::KEY_MAX_LENGTH / $glyphLength));
				$typeMaxLength = TypeHelper::getMaxLength($type);

				if ($typeMaxLength > $keyMaxLength)
				{
					$type = new ArrayObjectType(
						\array_merge($type->getArrayCopy(),
							[
								K::TYPE_MAX_LENGTH => $keyMaxLength
							]));
				}
			}

			return $type;
		}

		return new BasicType('TEXT');
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