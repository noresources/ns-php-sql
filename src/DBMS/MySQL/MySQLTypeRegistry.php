<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\SingletonTrait;
use NoreSources\SQL\DBMS\TypeRegistry;
use NoreSources\SQL\DBMS\MySQL\MySQLConstants as K;
use NoreSources\SQL\DBMS\Types\ArrayObjectType;

/**
 *
 * @see https://mariadb.com/kb/en/data-types/
 * @see https://dev.mysql.com/doc/refman/8.0/en/data-types.html
 *
 */
class MySQLTypeRegistry extends TypeRegistry
{

	use SingletonTrait;

	public function __construct()
	{
		parent::__construct(
			[
				'tinyint' => [
					K::TYPE_SIZE => 8,
					K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_SIGNNESS)
				],
				'smallint' => [
					K::TYPE_SIZE => 16,
					K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_SIGNNESS)
				],
				'mediumint' => [
					K::TYPE_SIZE => 24,
					K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_SIGNNESS)
				],
				'int' => [
					K::TYPE_SIZE => 32,
					K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_SIGNNESS)
				],
				'bigint' => [
					K::TYPE_SIZE => 64,
					K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_SIGNNESS)
				],
				'decimal' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_NUMBER,
					K::TYPE_MAX_LENGTH => 30,
					K::TYPE_DEFAULT_SCALE => 0,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_FRACTION_SCALE | K::TYPE_FLAG_SIGNNESS)
				],
				'float' => [
					K::TYPE_SIZE => 32, // not sure about that
					K::TYPE_DATA_TYPE => K::DATATYPE_FLOAT,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_FRACTION_SCALE | K::TYPE_FLAG_SIGNNESS)
				],
				'double' => [
					K::TYPE_SIZE => 64, // not sure about that
					K::TYPE_DATA_TYPE => K::DATATYPE_FLOAT,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_FRACTION_SCALE | K::TYPE_FLAG_SIGNNESS)
				],
				// Alias of tinyint (1)
				'boolean' => [
					K::TYPE_SIZE => 1,
					K::TYPE_DATA_TYPE => K::DATATYPE_BOOLEAN,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH)
				],
				'char' => [
					K::TYPE_MAX_LENGTH => 255,
					K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_MANDATORY_LENGTH),
					K::TYPE_PADDING_DIRECTION => K::TYPE_PADDING_DIRECTION_RIGHT,
					K::TYPE_PADDING_GLYPH => ' '
				],
				'varchar' => [
					K::TYPE_MAX_LENGTH => 65535,
					K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_MANDATORY_LENGTH)
				],
				'bit' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
					K::TYPE_DEFAULT_LENGTH => 1,
					K::TYPE_MAX_LENGTH => 64,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH)
				],
				'binary' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
					K::TYPE_MAX_LENGTH => 255,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_MANDATORY_LENGTH),
					K::TYPE_PADDING_DIRECTION => K::TYPE_PADDING_DIRECTION_RIGHT,
					K::TYPE_PADDING_GLYPH => "\0"
				],
				'varbinary' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
					K::TYPE_MAX_LENGTH => 255,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_MANDATORY_LENGTH)
				],
				'tinyblob' => [
					K::TYPE_MAX_LENGTH => 255,
					K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
					K::TYPE_DEFAULT_DATA_TYPE => K::DATATYPE_NULL
				],
				'blob' => [
					K::TYPE_MAX_LENGTH => 4294967295,
					K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
					K::TYPE_DEFAULT_DATA_TYPE => K::DATATYPE_NULL
				/**
				 *
				 * @see K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH )
				 */
				],
				'mediumblob' => [
					K::TYPE_MAX_LENGTH => 65535,
					K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
					K::TYPE_DEFAULT_DATA_TYPE => K::DATATYPE_NULL
				],
				'longblob' => [
					K::TYPE_MAX_LENGTH => 4294967295,
					K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
					K::TYPE_DEFAULT_DATA_TYPE => K::DATATYPE_NULL
				],
				'tinytext' => [
					K::TYPE_MAX_LENGTH => 255,
					K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
					K::TYPE_DEFAULT_DATA_TYPE => K::DATATYPE_NULL
				],
				/**
				 *
				 * @see An optional length M can be given for this type.
				 *      If this is done, MariaDB creates the column as the smallest TEXT type large
				 *      enough to hold values M characters long.
				 *
				 * @see https://mariadb.com/kb/en/text/
				 */
				'text' => [
					K::TYPE_MAX_LENGTH => 65535,
					K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
					K::TYPE_DEFAULT_DATA_TYPE => K::DATATYPE_NULL
				/**
				 *
				 * @see https://stackoverflow.com/questions/1827063/mysql-error-key-specification-without-a-key-length
				 */
				],
				'mediumtext' => [
					K::TYPE_MAX_LENGTH => 16777215,
					K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
					K::TYPE_DEFAULT_DATA_TYPE => K::DATATYPE_NULL
				],
				'longtext' => [
					K::TYPE_MAX_LENGTH => 4294967295,
					K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
					K::TYPE_DEFAULT_DATA_TYPE => K::DATATYPE_NULL
				],
				'date' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_DATE,
					K::TYPE_DEFAULT_DATA_TYPE => K::DATATYPE_TIMESTAMP
				],
				'datetime' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_DATETIME,
					K::TYPE_DEFAULT_DATA_TYPE => K::DATATYPE_TIMESTAMP
				],
				'time' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_TIME,
					K::TYPE_DEFAULT_DATA_TYPE => K::DATATYPE_TIMESTAMP
				],
				'timestamp' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_TIMESTAMP,
					K::TYPE_DEFAULT_DATA_TYPE => K::DATATYPE_TIMESTAMP
				],
				'enum' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
					K::TYPE_MAX_LENGTH => 2
				],
				'json' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
					K::TYPE_MEDIA_TYPE => 'application/json'
				],
				// MYSQLI identifiers
				MYSQLI_TYPE_GEOMETRY => [
					K::TYPE_NAME => 'geometry',
					K::TYPE_DATA_TYPE => K::DATATYPE_UNDEFINED
				],
				MYSQLI_TYPE_INTERVAL => [
					K::TYPE_NAME => 'interval',
					K::TYPE_DATA_TYPE => K::DATATYPE_UNDEFINED
				],
				MYSQLI_TYPE_NULL => [
					K::TYPE_NAME => 'null',
					K::TYPE_DATA_TYPE => K::DATATYPE_NULL
				],
				MYSQLI_TYPE_SET => [
					K::TYPE_NAME => 'set',
					K::TYPE_DATA_TYPE => K::DATATYPE_UNDEFINED
				],
				MYSQLI_TYPE_YEAR => [
					K::TYPE_NAME => 'year',
					K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
					K::TYPE_SIZE => 14
				]
			],

			// Aliases
			[
				MYSQLI_TYPE_BIT => 'bit',
				MYSQLI_TYPE_BLOB => 'blob',
				MYSQLI_TYPE_CHAR => 'char',
				MYSQLI_TYPE_DATE => 'date',
				MYSQLI_TYPE_DATETIME => 'datetime',
				MYSQLI_TYPE_DECIMAL => 'decimal',
				MYSQLI_TYPE_DOUBLE => 'double',
				MYSQLI_TYPE_ENUM => 'enum',
				MYSQLI_TYPE_FLOAT => 'float',
				MYSQLI_TYPE_INT24 => 'mediumint',
				MYSQLI_TYPE_JSON => 'json',
				MYSQLI_TYPE_LONG => 'mediumint',
				MYSQLI_TYPE_LONG_BLOB => 'longblob',
				MYSQLI_TYPE_LONGLONG => 'bigint',
				MYSQLI_TYPE_MEDIUM_BLOB => 'mediumblob',
				MYSQLI_TYPE_NEWDATE => 'date',
				MYSQLI_TYPE_NEWDECIMAL => 'decimal',
				MYSQLI_TYPE_SHORT => 'smallint',
				MYSQLI_TYPE_STRING => 'text',
				MYSQLI_TYPE_TIME => 'time',
				MYSQLI_TYPE_TIMESTAMP => 'timestamp',
				MYSQLI_TYPE_TINY => 'tinyint',
				MYSQLI_TYPE_TINY_BLOB => 'tinyblob',
				MYSQLI_TYPE_VAR_STRING => 'varchar',
				'integer' => 'int',
				'dec' => 'decimal',
				'numeric' => 'decimal',
				'fixed' => 'decimal',
				'double precision' => 'double',
				'real' => 'double',
				'char byte' => 'binary'
			]);
	}

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
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_SIGNNESS)
				],
				'smallint' => [
					K::TYPE_SIZE => 16,
					K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_SIGNNESS)
				],
				'mediumint' => [
					K::TYPE_SIZE => 24,
					K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_SIGNNESS)
				],
				'int' => [
					K::TYPE_SIZE => 32,
					K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_SIGNNESS)
				],
				'bigint' => [
					K::TYPE_SIZE => 64,
					K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_SIGNNESS)
				],
				'decimal' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_FLOAT,
					K::TYPE_MAX_LENGTH => 30,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_FRACTION_SCALE | K::TYPE_FLAG_SIGNNESS)
				],
				'float' => [
					K::TYPE_SIZE => 32, // not sure about that
					K::TYPE_DATA_TYPE => K::DATATYPE_FLOAT,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_FRACTION_SCALE | K::TYPE_FLAG_SIGNNESS)
				],
				'double' => [
					K::TYPE_SIZE => 64, // not sure about that
					K::TYPE_DATA_TYPE => K::DATATYPE_FLOAT,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_FRACTION_SCALE | K::TYPE_FLAG_SIGNNESS)
				],
				// Alias of tinyint (1)
				'boolean' => [
					K::TYPE_SIZE => 1,
					K::TYPE_DATA_TYPE => K::DATATYPE_BOOLEAN,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH)
				],
				'char' => [
					K::TYPE_MAX_LENGTH => 255,
					K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_MANDATORY_LENGTH),
					K::TYPE_PADDING_DIRECTION => K::TYPE_PADDING_DIRECTION_RIGHT,
					K::TYPE_PADDING_GLYPH => ' '
				],
				'varchar' => [
					K::TYPE_MAX_LENGTH => 65535,
					K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_MANDATORY_LENGTH)
				],
				'binary' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
					K::TYPE_MAX_LENGTH => 255,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_MANDATORY_LENGTH),
					K::TYPE_PADDING_DIRECTION => K::TYPE_PADDING_DIRECTION_RIGHT,
					K::TYPE_PADDING_GLYPH => '0'
				],
				'varbinary' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
					K::TYPE_MAX_LENGTH => 255,
					K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
					K::TYPE_FLAG_MANDATORY_LENGTH)
				],
				/**
				 *
				 * @note Type unit is "1 bit"
				 */
				'tinyblob' => [
					K::TYPE_MAX_LENGTH => 255,
					K::TYPE_DATA_TYPE => K::DATATYPE_BINARY
				],
				'blob' => [
					K::TYPE_MAX_LENGTH => 4294967295,
					K::TYPE_DATA_TYPE => K::DATATYPE_BINARY
				/**
				 *
				 * @see K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH )
				 */
				],
				'mediumblob' => [
					K::TYPE_MAX_LENGTH => 65535,
					K::TYPE_DATA_TYPE => K::DATATYPE_BINARY
				],
				'longblob' => [
					K::TYPE_MAX_LENGTH => 4294967295,
					K::TYPE_DATA_TYPE => K::DATATYPE_BINARY
				],
				'tinytext' => [
					K::TYPE_MAX_LENGTH => 255,
					K::TYPE_DATA_TYPE => K::DATATYPE_STRING
				],
				/**
				 *
				 * @see An optional length M can be given for this type.
				 *      If this is done, MariaDB creates the column as the smallest TEXT type large
				 *      enough to hold values M characters long.
				 *
				 * @see https://mariadb.com/kb/en/text/
				 */
				'text' => [
					K::TYPE_MAX_LENGTH => 65535,
					K::TYPE_DATA_TYPE => K::DATATYPE_STRING
				/**
				 *
				 * @see https://stackoverflow.com/questions/1827063/mysql-error-key-specification-without-a-key-length
				 */
				],
				'mediumtext' => [
					K::TYPE_MAX_LENGTH => 16777215,
					K::TYPE_DATA_TYPE => K::DATATYPE_STRING
				],
				'longtext' => [
					K::TYPE_MAX_LENGTH => 4294967295,
					K::TYPE_DATA_TYPE => K::DATATYPE_STRING
				],
				'date' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_DATE,
					K::TYPE_DEFAULT_DATA_TYPE => K::DATATYPE_TIMESTAMP
				],
				'datetime' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_DATETIME,
					K::TYPE_DEFAULT_DATA_TYPE => K::DATATYPE_TIMESTAMP
				],
				'time' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_TIME
				],
				'timestamp' => [
					K::TYPE_DATA_TYPE => K::DATATYPE_TIMESTAMP
				]
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
					\array_merge(self::$typesMap[$name]->getArrayCopy(),
						[
							K::TYPE_NAME => $alias
						]));
			}
		}

		return self::$typesMap;
	}

	private static $typesMap;
}