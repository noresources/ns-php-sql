<?php

/**
 * Update OID <-> type name -<> data type maps
 */
namespace NoreSources\SQL\DBMS\PostgreSQL;

use NoreSources\Container;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConstants as K;
require (__DIR__ . '/../vendor/autoload.php');

/**
 *
 * @see https://www.postgresql.org/docs/8.4/catalog-pg-type.html
 * @var Ambiguous $query
 */

$query = <<< EOF
SELECT t.oid, pg_catalog.format_type(t.oid, NULL) AS "name",
  pg_catalog.obj_description(t.oid, 'pg_type') as "desc",
  t.typlen as "length"
FROM pg_catalog.pg_type t
     LEFT JOIN pg_catalog.pg_namespace n ON n.oid = t.typnamespace
WHERE (t.typrelid = 0 OR (SELECT c.relkind = 'c' FROM pg_catalog.pg_class c WHERE c.oid = t.typrelid))
  AND NOT EXISTS(SELECT 1 FROM pg_catalog.pg_type el WHERE el.oid = t.typelem AND el.typarray = t.oid)
  AND pg_catalog.pg_type_is_visible(t.oid)
ORDER BY 2
EOF;

$typeFlags = [
	K::TYPE_FLAG_FRACTION_SCALE => 'K::TYPE_FLAG_FRACTION_SCALE',
	K::TYPE_FLAG_LENGTH => 'K::TYPE_FLAG_LENGTH',
	K::TYPE_FLAG_MANDATORY_LENGTH => 'K::TYPE_FLAG_MANDATORY_LENGTH',
	K::TYPE_FLAG_SIGNNESS => 'K::TYPE_FLAG_SIGNNESS'
];

$dataTypes = [
	K::DATATYPE_BINARY => 'K::DATATYPE_BINARY',
	K::DATATYPE_BOOLEAN => 'K::DATATYPE_BOOLEAN',
	K::DATATYPE_DATE => 'K::DATATYPE_DATE',
	K::DATATYPE_DATETIME => 'K::DATATYPE_DATETIME',
	K::DATATYPE_FLOAT => 'K::DATATYPE_FLOAT',
	K::DATATYPE_INTEGER => 'K::DATATYPE_INTEGER',
	K::DATATYPE_NULL => 'K::DATATYPE_NULL',
	K::DATATYPE_NUMBER => 'K::DATATYPE_NUMBER',
	K::DATATYPE_STRING => 'K::DATATYPE_STRING',
	K::DATATYPE_TIME => 'K::DATATYPE_TIME',
	K::DATATYPE_TIMESTAMP => 'K::DATATYPE_TIMESTAMP',
	K::DATATYPE_TIMEZONE => 'K::DATATYPE_TIMEZONE',
	K::DATATYPE_UNDEFINED => 'K::DATATYPE_UNDEFINED'
];

$typePropertiesMap = [
	// Range tool limited
	// 'abstime' => [
	// K::TYPE_DATA_TYPE => K::DATATYPE_DATETIME
	// ],
	'boolean' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_BOOLEAN
	],
	'bigint' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER
	],
	'bit varying' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
		K::TYPE_FLAGS => K::TYPE_FLAG_LENGTH,
		K::TYPE_MEDIA_TYPE => '	K::MEDIA_TYPE_BIT_STRING'
	],
	// Require a strict glyph count property / auto pad
	// 'bit'
	'bytea' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_BINARY
	],
	'"char"' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
		K::TYPE_MAX_LENGTH => 1
	],
	// Require strict glyph count / autopad
	// 'character'
	'character varying' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
		K::TYPE_FLAGS => K::TYPE_FLAG_LENGTH
	],
	// 'cstring' => [
	// K::TYPE_DATA_TYPE => K::DATATYPE_STRING
	//
	'date' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_DATE
	],
	'double precision' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_FLOAT
	],

	// This is an alias of int4
	'integer' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER
	],
	'json' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
		K::TYPE_MEDIA_TYPE => '\'application/json\''
	],
	'jsonb' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
		K::TYPE_MEDIA_TYPE => '\'application/json\''
	],
	// 'money' => [
	// K::TYPE_DATA_TYPE => K::DATATYPE_NUMBER,
	// K::TYPE_TEXT_PATTERN => 'TBD'
	// ],
	'numeric' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_NUMBER,
		K::TYPE_FLAGS => K::TYPE_FLAG_FRACTION_SCALE
	],

	// 'oid' => [
	// K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER
	// ],
	'real' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_FLOAT
	],
	// Too specific
	// 'reltime' => [
	// K::TYPE_DATA_TYPE => K::DATATYPE_DATETIME,
	// ],
	'smallint' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER
	],
	'text' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_STRING
	],
	'time without time zone' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_TIME
	],
	'timestamp without time zone' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_DATETIME
	],
	'timestamp with time zone' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_TIMESTAMP
	],
	'time with time zone' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_TIME | K::DATATYPE_TIMEZONE
	],
	// 'uuid' => [
	// K::TYPE_DATA_TYPE => K::DATATYPE_STRING
	// ],
	'xml' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
		K::TYPE_MEDIA_TYPE => '\'text/xml\''
	]
];

$connection = \pg_connect('');
$result = \pg_query($connection, $query);
$oidToTypeNames = [];
$typeNameOidMap = [];
$oidToDataType = [];
$oidDescriptions = [];

while ($row = pg_fetch_assoc($result))
{
	$oid = intval($row['oid']);
	$name = $row['name'];
	$oidDescriptions[$oid] = $row['desc'];
	$oidToTypeNames[$oid] = $name;
	$typeNameOidMap[$name] = $oid;
	$length = \intval($row['length']);
	$size = $length * 8;

	echo (sprintf('%-5d %-30.30s %3d %s', $oid, $name, $length,
		$row['desc']) . PHP_EOL);

	if (Container::keyExists($typePropertiesMap, $name))
	{
		if ($size > 0)
			$typePropertiesMap[$name][K::TYPE_SIZE] = $size;

		if ((Container::keyValue($typePropertiesMap[$name],
			K::TYPE_DATA_TYPE) & K::DATATYPE_TIMESTAMP) &&
			!Container::keyExists($typePropertiesMap[$name],
				K::TYPE_DEFAULT_DATA_TYPE))
			$typePropertiesMap[$name][K::TYPE_DEFAULT_DATA_TYPE] = K::DATATYPE_TIMESTAMP;

		$oidToDataType[$oid] = $typePropertiesMap[$name][K::COLUMN_DATA_TYPE];
	}
}

$filename = __DIR__ .
	'/../src/DBMS/PostgreSQL/PostgreSQLTypeRegistry.php';
$file = file_get_contents($filename);

$typePropertiesMapContent = Container::implode($typePropertiesMap,
	',' . PHP_EOL,
	function ($name, $properties) use ($typeNameOidMap, $oidDescriptions,
	$typeFlags, $dataTypes) {
		$cleanName = \str_replace('"', '', $name);
		if (!\array_key_exists($name, $typeNameOidMap))
			throw new \InvalidArgumentException(
				'"' . $cleanName . '" oid not found');
		$oid = $typeNameOidMap[$name];
		$s = '';
		$s .= "'" . $cleanName . "'" . ' => new ArrayObjectType([ ' .
		PHP_EOL;
		// name
		$s .= "'" . K::TYPE_NAME . "' => '" . $cleanName . "'," . PHP_EOL;
		foreach ($properties as $k => $v)
		{
			if ($k == K::TYPE_DATA_TYPE)
			{
				$k = 'K::TYPE_DATA_TYPE';
				if (Container::keyExists($dataTypes, $v))
					$v = Container::keyValue($dataTypes, $v, $v);
				else
				{
					$a = [];
					foreach ($dataTypes as $flag => $constant)
					{
						if (($v & $flag) == $flag)
							$a[] = $constant;
					}

					$v = '(' . \implode(' | ', $a) . ')';
				}
			}
			elseif ($k == K::TYPE_FLAGS)
			{
				$k = 'K::TYPE_FLAGS';
				$a = [];
				foreach ($typeFlags as $flag => $constant)
				{
					if (($v & $flag) == $flag)
						$a[] = $constant;
				}
				if (\count($a) == 0)
					continue;

				$v = '(' . \implode(' | ', $a) . ')';
			}
			else
				$k = "'" . $k . "'";
			$s .= $k . " => " . $v . ', ' . PHP_EOL;
		}
		$s .= '])';
		return $s;
	});

$typeNameOidMapContent = Container::implode($typeNameOidMap,
	',' . PHP_EOL,
	function ($name, $oid) {
		return "'" . $name . '\' => ' . $oid;
	});

$file = preg_replace(
	',(--<typeNameOidMap>--).*?(--</typeNameOidMap>--),sm',
	'\1 */' . PHP_EOL . $typeNameOidMapContent . PHP_EOL . '/* \2',
	$file);

$file = preg_replace(
	',(--<typeProperties>--).*?(--</typeProperties>--),sm',
	'\1 */' . PHP_EOL . $typePropertiesMapContent . PHP_EOL . '/* \2',
	$file);

file_put_contents($filename, $file);