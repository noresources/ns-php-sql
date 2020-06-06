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
use NoreSources\SQL\DBMS\ArrayObjectType;
use NoreSources\SQL\DBMS\BasicType;
use NoreSources\SQL\DBMS\TypeHelper;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConstants as K;
use NoreSources\SQL\Structure\ColumnDescriptionInterface;

class PostgreSQLType
{

	public static function getPostgreSQLTypes()
	{
		if (!Container::isArray(self::$typePropertiesMap))
			self::initialize();

		return self::$typePropertiesMap;
	}

	/**
	 *
	 * @param string|integer $oidOrName
	 *        	Type name or OID
	 * @return \NoreSources\\SQL\DBMS\TypeInterface Type properties or NULL
	 */
	public static function getPostgreSQLType($oidOrName)
	{
		if (!Container::isArray(self::$typePropertiesMap))
			self::initialize();

		if (!\is_integer($oidOrName))
			$oidOrName = Container::keyValue(self::$typeNameOidMap, $oidOrName, false);

		return Container::keyValue(self::$typePropertiesMap, $oidOrName);
	}

	/**
	 *
	 * @param integer $oid
	 * @return mixed|array|\ArrayAccess|\Psr\Container\ContainerInterface|\Traversable
	 */
	public static function oidToDataType($oid)
	{
		if (!Container::isArray(self::$typePropertiesMap))
			self::initialize();

		if (!Container::keyExists(self::$typePropertiesMap, $oid))
			return K::DATATYPE_UNDEFINED;

		return self::$typePropertiesMap[$oid][K::TYPE_NAME];
	}

	public static function typeNameToDataType($name)
	{
		if (!\is_array(self::$typeNameOidMap))
			self::initialize();

		if (Container::keyExists(self::$typeNameOidMap, $name))
			throw new \InvalidArgumentException('Unknown type name ' . $name);

		return self::oidToDataType(self::$typeNameOidMap[$name]);
	}

	/**
	 *
	 * @param integer $dataType
	 *        	Generic data type
	 * @return TypeInterface
	 */
	public static function columnPropertyToType(ColumnDescriptionInterface $column)
	{
		$columnFlags = $column->getColumnProperty(K::COLUMN_FLAGS);

		if (!\is_array(self::$typePropertiesMap))
			self::initialize();

		if ($columnFlags & K::COLUMN_FLAG_AUTO_INCREMENT)
		{
			return new BasicType('serial');
		}

		$dataType = K::DATATYPE_UNDEFINED;
		if ($column->hasColumnProperty(K::COLUMN_DATA_TYPE))
			$dataType = $column->getColumnProperty(K::COLUMN_DATA_TYPE);

		$matchingTypes = \array_filter(self::$typePropertiesMap,
			function ($type) use ($dataType) {
				$typeDataType = $type->get(K::TYPE_DATA_TYPE);
				return (($typeDataType & $dataType) == $dataType);
			});

		if (\count($matchingTypes) == 0)
			throw new \RuntimeException('No PostgreSQL type found for column type ' . $dataType);

		$count = \count($matchingTypes);

		if ($count == 1)
		{
			list ($oid, $type) = Container::first($matchingTypes);
			return $type;
		}

		if ($column->hasColumnProperty(K::COLUMN_LENGTH))
		{
			$glyphCount = intval($column->getColumnProperty(K::COLUMN_LENGTH));
			if ($dataType == K::DATATYPE_INTEGER)
			{
				// Prefer fixed precision types

				$filtered = \array_filter($matchingTypes,
					function ($type) use ($glyphCount) {
						if (!$type->has(K::TYPE_SIZE))
							return false;
						$maxDigit = TypeHelper::getMaxGlyphCount($type);
						return ($maxDigit >= $glyphCount);
					});

				$c = \count($filtered);
				if ($c)
				{
					// Prefer the smallest type
					usort($filtered,
						function ($a, $b) {
							$sa = $a->get(K::TYPE_SIZE);
							$sb = $b->get(K::TYPE_SIZE);
							return ($sa - $sb);
						});

					$count = $c;
					$matchingTypes = $filtered;
				}
			}

			$filtered = \array_filter($matchingTypes,
				function ($type) {
					$flags = TypeHelper::getProperty($type, K::TYPE_FLAGS);
					return (($flags & K::TYPE_FLAG_LENGTH) == K::TYPE_FLAG_LENGTH);
				});

			$c = \count($filtered);

			if ($c)
			{
				$count = $c;
				$matchingTypes = $filtered;
			}

			// Type size filtering if integer
		}
		else // no glyph count
		{
			// Prefer types without size spec

			$filtered = \array_filter($matchingTypes,
				function ($type) {
					$typeFlags = TypeHelper::getProperty($type, K::TYPE_FLAGS);
					return ((($typeFlags & K::TYPE_FLAG_LENGTH) == 0) && !$type->has(K::TYPE_SIZE));
				});

			$c = \count($filtered);

			if ($c)
			{
				$count = $c;
				$matchingTypes = $filtered;
			}
		}

		if ($count)
		{
			$filtered = \array_filter($matchingTypes,
				function ($type) use ($column) {
					if ($column->hasColumnProperty(K::COLUMN_MEDIA_TYPE))
					{
						if ($type->has(K::TYPE_MEDIA_TYPE))
							return (\strval($column->getProperty(K::COLUMN_MEDIA_TYPE)) ==
							\strval($type->get(K::TYPE_MEDIA_TYPE)));
						return false;
					}
					else
						return !$type->has(K::TYPE_MEDIA_TYPE);
				});

			$c = \count($filtered);

			if ($c)
			{
				$count = $c;
				$matchingTypes = $filtered;
			}
		}

		if ($count == 1)
		{
			list ($oid, $type) = Container::first($matchingTypes);
			return $type;
		}
		elseif ($count)
		{
			list ($oid, $type) = Container::first($filtered);
			return $type;
		}

		return new BasicType('text');
	}

	private static function initialize()
	{
		self::$typeNameOidMap = [ /* Auto-generated code --<typeNameOidMap>-- */
			'abstime' => 702,
			'aclitem' => 1033,
			'"any"' => 2276,
			'anyarray' => 2277,
			'anyelement' => 2283,
			'anyenum' => 3500,
			'anynonarray' => 2776,
			'anyrange' => 3831,
			'bigint' => 20,
			'bit' => 1560,
			'bit varying' => 1562,
			'boolean' => 16,
			'box' => 603,
			'bytea' => 17,
			'"char"' => 18,
			'character' => 1042,
			'character varying' => 1043,
			'cid' => 29,
			'cidr' => 650,
			'circle' => 718,
			'cstring' => 2275,
			'date' => 1082,
			'daterange' => 3912,
			'double precision' => 701,
			'event_trigger' => 3838,
			'fdw_handler' => 3115,
			'gtsvector' => 3642,
			'index_am_handler' => 325,
			'inet' => 869,
			'int2vector' => 22,
			'int4range' => 3904,
			'int8range' => 3926,
			'integer' => 23,
			'internal' => 2281,
			'interval' => 1186,
			'json' => 114,
			'jsonb' => 3802,
			'language_handler' => 2280,
			'line' => 628,
			'lseg' => 601,
			'macaddr' => 829,
			'macaddr8' => 774,
			'money' => 790,
			'name' => 19,
			'numeric' => 1700,
			'numrange' => 3906,
			'oid' => 26,
			'oidvector' => 30,
			'opaque' => 2282,
			'path' => 602,
			'pg_ddl_command' => 32,
			'pg_dependencies' => 3402,
			'pg_lsn' => 3220,
			'pg_ndistinct' => 3361,
			'pg_node_tree' => 194,
			'point' => 600,
			'polygon' => 604,
			'real' => 700,
			'record' => 2249,
			'refcursor' => 1790,
			'regclass' => 2205,
			'regconfig' => 3734,
			'regdictionary' => 3769,
			'regnamespace' => 4089,
			'regoper' => 2203,
			'regoperator' => 2204,
			'regproc' => 24,
			'regprocedure' => 2202,
			'regrole' => 4096,
			'regtype' => 2206,
			'reltime' => 703,
			'smallint' => 21,
			'smgr' => 210,
			'text' => 25,
			'tid' => 27,
			'timestamp without time zone' => 1114,
			'timestamp with time zone' => 1184,
			'time without time zone' => 1083,
			'time with time zone' => 1266,
			'tinterval' => 704,
			'trigger' => 2279,
			'tsm_handler' => 3310,
			'tsquery' => 3615,
			'tsrange' => 3908,
			'tstzrange' => 3910,
			'tsvector' => 3614,
			'txid_snapshot' => 2970,
			'unknown' => 705,
			'uuid' => 2950,
			'void' => 2278,
			'xid' => 28,
			'xml' => 142
			/* --</typeNameOidMap>-- */
		];

		self::$typePropertiesMap = new \ArrayObject(
			[ /* --<typeProperties>-- */
				16 => new ArrayObjectType(
					[
						'typename' => 'boolean',
						'datatype' => K::DATATYPE_BOOLEAN,
						'typesize' => 8
					]),
				20 => new ArrayObjectType(
					[
						'typename' => 'bigint',
						'datatype' => K::DATATYPE_INTEGER,
						'typesize' => 64
					]),
				17 => new ArrayObjectType(
					[
						'typename' => 'bytea',
						'datatype' => K::DATATYPE_BINARY
					]),
				18 => new ArrayObjectType(
					[
						'typename' => 'char',
						'datatype' => K::DATATYPE_STRING,
						'maxlength' => 1,
						'typesize' => 8
					]),
				1043 => new ArrayObjectType(
					[
						'typename' => 'character varying',
						'datatype' => K::DATATYPE_STRING,
						'typeflags' => 15
					]),
				1082 => new ArrayObjectType(
					[
						'typename' => 'date',
						'datatype' => K::DATATYPE_DATE,
						'typesize' => 32
					]),
				701 => new ArrayObjectType(
					[
						'typename' => 'double precision',
						'datatype' => K::DATATYPE_FLOAT,
						'typesize' => 64
					]),
				23 => new ArrayObjectType(
					[
						'typename' => 'integer',
						'datatype' => K::DATATYPE_INTEGER,
						'typesize' => 32
					]),
				114 => new ArrayObjectType(
					[
						'typename' => 'json',
						'datatype' => K::DATATYPE_STRING,
						'mediatype' => 'application/json'
					]),
				3802 => new ArrayObjectType(
					[
						'typename' => 'jsonb',
						'datatype' => K::DATATYPE_BINARY,
						'mediatype' => 'application/json'
					]),
				1700 => new ArrayObjectType(
					[
						'typename' => 'numeric',
						'datatype' => K::DATATYPE_NUMBER,
						'typeflags' => 15
					]),
				700 => new ArrayObjectType(
					[
						'typename' => 'real',
						'datatype' => K::DATATYPE_FLOAT,
						'typesize' => 32
					]),
				21 => new ArrayObjectType(
					[
						'typename' => 'smallint',
						'datatype' => K::DATATYPE_INTEGER,
						'typesize' => 16
					]),
				25 => new ArrayObjectType(
					[
						'typename' => 'text',
						'datatype' => K::DATATYPE_STRING
					]),
				1083 => new ArrayObjectType(
					[
						'typename' => 'time without time zone',
						'datatype' => K::DATATYPE_TIME,
						'typesize' => 64
					]),
				1114 => new ArrayObjectType(
					[
						'typename' => 'timestamp without time zone',
						'datatype' => K::DATATYPE_DATETIME,
						'typesize' => 64
					]),
				1184 => new ArrayObjectType(
					[
						'typename' => 'timestamp with time zone',
						'datatype' => K::DATATYPE_TIMESTAMP,
						'typesize' => 64
					]),
				1266 => new ArrayObjectType(
					[
						'typename' => 'time with time zone',
						'datatype' => K::DATATYPE_TIME | K::DATATYPE_TIMEZONE,
						'typesize' => 96
					]),
				142 => new ArrayObjectType(
					[
						'typename' => 'xml',
						'datatype' => K::DATATYPE_STRING,
						'mediatype' => 'text/xml'
					])
				/* --</typeProperties>-- */
			]);
	}

	/**
	 * PostgreSQL type properties.
	 * Key is the PostgreSQL type OID
	 *
	 * @var TypeInterface[]
	 */
	private static $typePropertiesMap;

	/**
	 * Type name to Type OID map.
	 *
	 * @var int[] Key is the type name, value is the corresponding OID
	 */
	private static $typeNameOidMap;
}