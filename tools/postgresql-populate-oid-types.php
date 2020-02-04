<?php

/**
 * Update OID <-> type name -<> data type maps
 */
namespace NoreSources\SQL\DBMS\PostgreSQL;

use NoreSources\Container;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConstants as K;
require (__DIR__ . '/../vendor/autoload.php');

$query = <<< EOF
SELECT t.oid, pg_catalog.format_type(t.oid, NULL) AS "name",
  pg_catalog.obj_description(t.oid, 'pg_type') as "desc"
FROM pg_catalog.pg_type t
     LEFT JOIN pg_catalog.pg_namespace n ON n.oid = t.typnamespace
WHERE (t.typrelid = 0 OR (SELECT c.relkind = 'c' FROM pg_catalog.pg_class c WHERE c.oid = t.typrelid))
  AND NOT EXISTS(SELECT 1 FROM pg_catalog.pg_type el WHERE el.oid = t.typelem AND el.typarray = t.oid)
  AND pg_catalog.pg_type_is_visible(t.oid)
ORDER BY 2
EOF;

$typePropertiesMap = [
	'abstime' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_DATETIME'
	],
	'boolean' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_BOOLEAN'
	],
	'bigint' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_INTEGER',
		K::TYPE_PROPERTY_SIZE => 8 * 18
	],
	//  Require a strict glyph count property
	// 'bit' => [
	// 		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_STRING',
	// 		K::TYPE_PROPERTY_SIZE => 1
	// ],

	/* @todo A way to make distinction with "text" */

	// 'bit varying' => [
	// 		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_STRING',
	// 		K::TYPE_PROPERTY_GLYPH_COUNT => 'true'
	// ],
	'bytea' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_BINARY'
	],
	'"char"' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_STRING',
		K::TYPE_PROPERTY_SIZE => 1
	],
	// 'character' => [
	// 	K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_STRING',
	// K::COLUMN_PROPERTY_GLYPH_COUNT => 'true',
	// K::TYPE_PROPERTY_PADDING_DIRECTION => 1
	// ],
	'character varying' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_STRING',
		K::TYPE_PROPERTY_GLYPH_COUNT => 'true'
	],
	// 	'cstring' => [
	// 		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_STRING'
	// 	],
	'double precision' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_FLOAT',
		K::TYPE_PROPERTY_SIZE => 8 * 8
	],
	// 'inet' => [
	// 		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_STRING',
	// 		K::COLUMN_PROPERTY_TEXT_PATTERN => 'TBD',
	// 		K::TYPE_PROPERTY_SIZE => (8 * 4)
	// 	],
	'integer' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_INTEGER'
	],
	'json' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_STRING',
		K::TYPE_PROPERTY_MEDIA_TYPE => '\'application/json\''
	],
	'jsonb' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_BINARY'
	],
	// 'money' => [
	// 	K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_NUMBER',
	// 	K::COLUMN_PROPERTY_TEXT_PATTERN => 'TBD'
	// ],
	'numeric' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_NUMBER',
		K::TYPE_PROPERTY_GLYPH_COUNT => 'true',
		K::TYPE_PROPERTY_FRACTION_SCALE => 'true'
	],

	//	'oid' => [
	//		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_INTEGER'
	//	],
	'real' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_FLOAT'
	],
	'reltime' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_DATETIME'
	],
	'smallint' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_INTEGER',
		K::TYPE_PROPERTY_SIZE => (8 * 2)
	],
	'text' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_STRING'
	],
	'time without time zone' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_TIME'
	],
	'timestamp without time zone' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_DATETIME'
	],
	'timestamp with time zone' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_TIMESTAMP'
	],
	'time with time zone' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_TIME | K::DATATYPE_TIMEZONE'
	],
	// 'uuid' => [
	// 	K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_STRING'
	// ],
	'xml' => [
		K::COLUMN_PROPERTY_DATA_TYPE => 'K::DATATYPE_STRING',
		K::TYPE_PROPERTY_MEDIA_TYPE => '\'text/xml\''
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

	echo (sprintf('%-5d %-30.30s %s', $oid, $name, $row['desc']) . PHP_EOL);

	if (\array_key_exists($name, $typePropertiesMap))
	{
		$oidToDataType[$oid] = $typePropertiesMap[$name][K::COLUMN_PROPERTY_DATA_TYPE];
	}
}

$filename = __DIR__ . '/../src/DBMS/PostgreSQL/PostgreSQLType.php';
$file = file_get_contents($filename);

$typePropertiesMapContent = Container::implode($typePropertiesMap, ',' . PHP_EOL,
	function ($name, $properties) use ($typeNameOidMap, $oidDescriptions) {
		if (!\array_key_exists($name, $typeNameOidMap))
			throw new \InvalidArgumentException('"' . $name . '" oid not found');
		$oid = $typeNameOidMap[$name];
		$s = '';
		$s .= $oid . ' => new ArrayObjectType([ ' . PHP_EOL;
		// name
		$s .= "'" . K::TYPE_PROPERTY_NAME . "' => '" . $name . "'," . PHP_EOL;
		foreach ($properties as $k => $v)
		{
			$s .= "'" . $k . "' => " . $v . ', ' . PHP_EOL;
		}
		$s .= '])';
		return $s;
	});

$typeNameOidMapContent = Container::implode($typeNameOidMap, ',' . PHP_EOL,
	function ($name, $oid) {
		return "'" . $name . '\' => ' . $oid;
	});

$file = preg_replace(',(--<typeNameOidMap>--).*?(--</typeNameOidMap>--),sm',
	'\1 */' . PHP_EOL . $typeNameOidMapContent . PHP_EOL . '/* \2', $file);

$file = preg_replace(',(--<typeProperties>--).*?(--</typeProperties>--),sm',
	'\1 */' . PHP_EOL . $typePropertiesMapContent . PHP_EOL . '/* \2', $file);

file_put_contents($filename, $file);