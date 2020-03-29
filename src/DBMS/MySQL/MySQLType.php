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

use NoreSources\SQL\DBMS\ArrayObjectType;
use NoreSources\SQL\DBMS\MySQL\MySQLConstants as K;

/**
 *
 * @see https://mariadb.com/kb/en/data-types/
 * @see https://dev.mysql.com/doc/refman/8.0/en/data-types.html
 *
 */
class MySQLType
{

	/**
	 *
	 * @return \ArrayObject
	 */
	public static function getMySQLTypes()
	{
		if (self::$typesMap instanceof \ArrayObject)
			return self::$typesMap;

		self::$typesMap = new \ArrayObject(
			[
				'tinyint' => [
					K::TYPE_SIZE => 8,
					K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH | K::TYPE_FLAG_NULLABLE |
					K::TYPE_FLAG_DEFAULT_VALUE | K::TYPE_FLAG_SIGNNESS)
				],
				'smallint' => [
					K::TYPE_SIZE => 16,
					K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH | K::TYPE_FLAG_NULLABLE |
					K::TYPE_FLAG_DEFAULT_VALUE | K::TYPE_FLAG_SIGNNESS)
				],
				'mediumint' => [
					K::TYPE_SIZE => 24,
					K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH | K::TYPE_FLAG_NULLABLE |
					K::TYPE_FLAG_DEFAULT_VALUE | K::TYPE_FLAG_SIGNNESS)
				],
				'int' => [
					K::TYPE_SIZE => 32,
					K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH | K::TYPE_FLAG_NULLABLE |
					K::TYPE_FLAG_DEFAULT_VALUE | K::TYPE_FLAG_SIGNNESS)
				],
				'bigint' => [
					K::TYPE_SIZE => 64,
					K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH | K::TYPE_FLAG_NULLABLE |
					K::TYPE_FLAG_DEFAULT_VALUE | K::TYPE_FLAG_SIGNNESS)
				],
				'decimal' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_FLOAT,
					K::TYPE_MAX_LENGTH => 30,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH | K::TYPE_FLAG_FRACTION_SCALE |
					K::TYPE_FLAG_DEFAULT_VALUE | K::TYPE_FLAG_NULLABLE | K::TYPE_FLAG_SIGNNESS)
				],
				'float' => [
					K::TYPE_SIZE => 32, // not sure about that
					K::TYPE_DATA_TYPE => K::DATATYPE_FLOAT,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH | K::TYPE_FLAG_FRACTION_SCALE |
					K::TYPE_FLAG_DEFAULT_VALUE | K::TYPE_FLAG_NULLABLE | K::TYPE_FLAG_SIGNNESS)
				],
				'double' => [
					K::TYPE_SIZE => 64, // not sure about that
					K::TYPE_DATA_TYPE => K::DATATYPE_FLOAT,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH | K::TYPE_FLAG_FRACTION_SCALE |
					K::TYPE_FLAG_DEFAULT_VALUE | K::TYPE_FLAG_NULLABLE | K::TYPE_FLAG_SIGNNESS)
				],
				// Alias of tinyint (1)
				'boolean' => [
					K::TYPE_SIZE => 1,
					K::TYPE_DATA_TYPE => K::DATATYPE_BOOLEAN,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH | K::TYPE_FLAG_NULLABLE |
					K::TYPE_FLAG_DEFAULT_VALUE)
				],
				'char' => [
					K::TYPE_MAX_LENGTH => 255,
					K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH | K::TYPE_FLAG_NULLABLE |
					K::TYPE_FLAG_DEFAULT_VALUE | K::TYPE_FLAG_MANDATORY_LENGTH),
					K::TYPE_PADDING_DIRECTION => K::TYPE_PADDING_DIRECTION_RIGHT,
					K::TYPE_PADDING_GLYPH => ' '
				],
				'varchar' => [
					K::TYPE_MAX_LENGTH => 65535,
					K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH | K::TYPE_FLAG_NULLABLE |
					K::TYPE_FLAG_DEFAULT_VALUE | K::TYPE_FLAG_MANDATORY_LENGTH)
				],
				'binary' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
					K::TYPE_MAX_LENGTH => 255,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH | K::TYPE_FLAG_NULLABLE |
					K::TYPE_FLAG_DEFAULT_VALUE | K::TYPE_FLAG_MANDATORY_LENGTH),
					K::TYPE_PADDING_DIRECTION => K::TYPE_PADDING_DIRECTION_RIGHT,
					K::TYPE_PADDING_GLYPH => "\0"
				],
				'varbinary' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
					K::TYPE_MAX_LENGTH => 255,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH | K::TYPE_FLAG_NULLABLE |
					K::TYPE_FLAG_DEFAULT_VALUE | K::TYPE_FLAG_MANDATORY_LENGTH)
				],
				'tinyblob' => [
					K::TYPE_MAX_LENGTH => 255,
					K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
					K::TYPE_FLAGS => (K::TYPE_FLAG_NULLABLE)
				],
				'blob' => [
					K::TYPE_MAX_LENGTH => 4294967295,
					K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
					/**
					 *
					 * @see K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH | K::TYPE_FLAG_NULLABLE)
					 */
					//K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH | K::TYPE_FLAG_NULLABLE)
					K::TYPE_FLAGS => (K::TYPE_FLAG_NULLABLE)
				],
				'mediumblob' => [
					K::TYPE_MAX_LENGTH => 65535,
					K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
					K::TYPE_FLAGS => (K::TYPE_FLAG_NULLABLE)
				],
				'longblob' => [
					K::TYPE_MAX_LENGTH => 4294967295,
					K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
					K::TYPE_FLAGS => (K::TYPE_FLAG_NULLABLE)
				],
				'tinytext' => [
					K::TYPE_MAX_LENGTH => 255,
					K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
					K::TYPE_FLAGS => (K::TYPE_FLAG_NULLABLE)
				],
				/**
				 *
				 * @see An optional length M can be given for this type.
				 *      If this is done, MariaDB creates the column as the smallest TEXT type large enough to hold values M characters long.
				 *
				 * @see https://mariadb.com/kb/en/text/
				 */
				'text' => [
					K::TYPE_MAX_LENGTH => 65535,
					K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
					/**
					 *
					 * @see https://stackoverflow.com/questions/1827063/mysql-error-key-specification-without-a-key-length
					 */
					// K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH | K::TYPE_FLAG_NULLABLE)
					K::TYPE_FLAGS => (K::TYPE_FLAG_NULLABLE)
				],
				'mediumtext' => [
					K::TYPE_MAX_LENGTH => 16777215,
					K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
					K::TYPE_FLAGS => (K::TYPE_FLAG_NULLABLE)
				],
				'longtext' => [
					K::TYPE_MAX_LENGTH => 4294967295,
					K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
					K::TYPE_FLAGS => (K::TYPE_FLAG_NULLABLE)
				],
				'date' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_DATE
				],
				'datetime' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_DATETIME
				],
				'time' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_TIME
				],
				'timestamp' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_TIMESTAMP
				]
			/**
			 *
			 * @todo JSON
			 * @see https://mariadb.com/kb/en/json-data-type/
			 */
			]);

		$alias = [
			'int' => [
				'integer'
			],
			'decimal' => [
				'dec',
				'numeric',
				'fixed'
			],
			'double' => [
				'double precision',
				'real'
			],
			'binary' => [
				'char byte'
			]
		];

		foreach (self::$typesMap as $name => $type)
		{
			self::$typesMap[$name] = new ArrayObjectType(
				\array_merge($type, [
					K::TYPE_NAME => $name
				]));
		}

		foreach ($alias as $name => $table)
		{
			foreach ($table as $alias)
			{
				self::$typesMap[$alias] = new ArrayObjectType(
					\array_merge(self::$typesMap[$name]->getArrayCopy(), [
						K::TYPE_NAME => $alias
					]));
			}
		}

		return self::$typesMap;
	}

	private static $typesMap;
}