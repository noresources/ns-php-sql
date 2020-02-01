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
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConstants as K;

class PostgreSQLType
{

	/**
	 *
	 * @param unknown $oid
	 * @return mixed|array|\ArrayAccess|\Psr\Container\ContainerInterface|\Traversable
	 */
	public static function oidToDataType($oid)
	{
		if (\is_array(self::$oidToDataTypeMap))
			self::initialize();

		return Container::keyValue(self::$oidToDataTypeMap, $oid, K::DATATYPE_UNDEFINED);
	}

	public static function typeNameToDataType($name)
	{
		if (!\is_array(self::$typeNameToDataTypeMap))
			self::initialize();

		return Container::keyValue(self::$typeNameToDataTypeMap, $name, K::DATATYPE_UNDEFINED);
	}

	/**
	 *
	 * @param integer $dataType
	 *        	Generic data type
	 * @return string Most accurate PostgreSQL data type name
	 */
	public static function dataTypeToTypeName($dataType)
	{
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
				return 'integer';
			}

			return 'real';
		}

		if ($dataType == K::DATATYPE_BINARY)
			return 'bytea';
		elseif ($dataType == K::DATATYPE_BOOLEAN)
			return 'boolean';

		return 'text';
	}

	private static function initialize()
	{
		self::$oidToDataTypeMap = [ /* --<oidToDataType>-- */
			/* abstime: absolute, limited-range date and time (Unix system time) */
			702 => K::DATATYPE_DATETIME,
/* bigint: ~18 digit integer, 8-byte storage */
20 => K::DATATYPE_INTEGER,
/* bit: fixed-length bit string */
1560 => K::DATATYPE_STRING,
/* bit varying: variable-length bit string */
1562 => K::DATATYPE_STRING,
/* boolean: boolean, 'true'/'false' */
16 => K::DATATYPE_BOOLEAN,
/* bytea: variable-length string, binary values escaped */
17 => K::DATATYPE_BINARY,
/* character: char(length), blank-padded string, fixed storage length */
1042 => K::DATATYPE_STRING,
/* character varying: varchar(length), non-blank-padded string, variable storage length */
1043 => K::DATATYPE_STRING,
/* cstring:  */
2275 => K::DATATYPE_STRING,
/* double precision: double-precision floating point number, 8-byte storage */
701 => K::DATATYPE_FLOAT,
/* integer: -2 billion to 2 billion integer, 4-byte storage */
23 => K::DATATYPE_INTEGER,
/* json:  */
114 => K::DATATYPE_STRING,
/* jsonb: Binary JSON */
3802 => K::DATATYPE_BINARY,
/* numeric: numeric(precision, decimal), arbitrary precision number */
1700 => K::DATATYPE_NUMBER,
/* oid: object identifier(oid), maximum 4 billion */
26 => K::DATATYPE_INTEGER,
/* real: single-precision floating point number, 4-byte storage */
700 => K::DATATYPE_FLOAT,
/* reltime: relative, limited-range time interval (Unix delta time) */
703 => K::DATATYPE_DATETIME,
/* smallint: -32 thousand to 32 thousand, 2-byte storage */
21 => K::DATATYPE_INTEGER,
/* text: variable-length string, no limit specified */
25 => K::DATATYPE_STRING,
/* timestamp without time zone: date and time */
1114 => K::DATATYPE_DATETIME,
/* timestamp with time zone: date and time with time zone */
1184 => K::DATATYPE_TIMESTAMP,
/* time without time zone: time of day */
1083 => K::DATATYPE_TIME,
/* time with time zone: time of day with time zone */
1266 => K::DATATYPE_TIME | K::DATATYPE_TIMEZONE,
/* uuid: UUID datatype */
2950 => K::DATATYPE_STRING,
/* xml: XML content */
142 => K::DATATYPE_STRING
			/* --</oidToDataType>-- */
		];

		self::$typeNameToDataTypeMap = [ /* --<typeNameToDataType>-- */
			'abstime' => K::DATATYPE_DATETIME,
			'boolean' => K::DATATYPE_BOOLEAN,
			'bigint' => K::DATATYPE_INTEGER,
			'bit' => K::DATATYPE_STRING,
			'bit varying' => K::DATATYPE_STRING,
			'bytea' => K::DATATYPE_BINARY,
			'char' => K::DATATYPE_STRING,
			'character' => K::DATATYPE_STRING,
			'character varying' => K::DATATYPE_STRING,
			'cstring' => K::DATATYPE_STRING,
			'double precision' => K::DATATYPE_FLOAT,
			'integer' => K::DATATYPE_INTEGER,
			'json' => K::DATATYPE_STRING,
			'jsonb' => K::DATATYPE_BINARY,
			'numeric' => K::DATATYPE_NUMBER,
			'oid' => K::DATATYPE_INTEGER,
			'real' => K::DATATYPE_FLOAT,
			'reltime' => K::DATATYPE_DATETIME,
			'smallint' => K::DATATYPE_INTEGER,
			'text' => K::DATATYPE_STRING,
			'time without time zone' => K::DATATYPE_TIME,
			'timestamp without time zone' => K::DATATYPE_DATETIME,
			'timestamp with time zone' => K::DATATYPE_TIMESTAMP,
			'time with time zone' => K::DATATYPE_TIME | K::DATATYPE_TIMEZONE,
			'uuid' => K::DATATYPE_STRING,
			'xml' => K::DATATYPE_STRING,
			'' => K::DATATYPE_UNDEFINED
			/* --</typeNameToDataType>-- */
		];
	}

	/**
	 *
	 * @var int[]
	 */
	private static $oidToDataTypeMap;

	/**
	 *
	 * @var int[]
	 */
	private static $typeNameToDataTypeMap;
}