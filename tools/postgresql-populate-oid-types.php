<?php

/**
 * Update OID <-> type name -<> data type maps
 */
namespace NoreSources\SQL\DBMS\PostgreSQL;

use NoreSources\Container\Container;
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

$constantsClass = new \ReflectionClass(K::class);

$constants = $constantsClass->getConstants();

function reverseConstantMapFromPrefix($constants, $prefix)
{
	$a = [];
	$s = \strlen($prefix);
	foreach ($constants as $name => $value)
	{
		if (\substr($name, 0, $s) != $prefix)
			continue;
		$a[$value] = 'K::' . $name;
	}
	return $a;
}

$typeFlagConstants = reverseConstantMapFromPrefix($constants,
	'TYPE_FLAG_');
$typePaddingDirectionConstants = reverseConstantMapFromPrefix(
	$constants, 'TYPE_PADDING_DIRECTION_');

$mediaTypeConstants = reverseConstantMapFromPrefix($constants,
	'MEDIA_TYPE_');

$dataTypeConstants = reverseConstantMapFromPrefix($constants,
	'DATATYPE_');

$typePropertyConstants = Container::filter(
	reverseConstantMapFromPrefix($constants, 'TYPE_'),
	function ($k, $v) use ($typeFlagConstants,
	$typePaddingDirectionConstants) {
		return !(Container::valueExists($typeFlagConstants, $v) ||
		Container::valueExists($typePaddingDirectionConstants, $v));
	});

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
		K::TYPE_MEDIA_TYPE => K::MEDIA_TYPE_BIT_STRING
	],
	// Require a strict glyph count property / auto pad
	// 'bit'
	'bytea' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_BINARY
	],
	'"char"' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
		K::TYPE_MAX_LENGTH => 10485760,
		K::TYPE_FLAGS => K::TYPE_FLAG_LENGTH,
		K::TYPE_PADDING_DIRECTION => K::TYPE_PADDING_DIRECTION_RIGHT,
		K::TYPE_PADDING_GLYPH => ' '
	],
	/*'character' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
		K::TYPE_FLAGS => K::TYPE_FLAG_LENGTH,
		K::TYPE_PADDING_DIRECTION => K::TYPE_PADDING_DIRECTION_RIGHT,
		K::TYPE_PADDING_GLYPH => ' '
	], */
	 'character varying' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
		K::TYPE_FLAGS => K::TYPE_FLAG_LENGTH,
		K::TYPE_MAX_LENGTH => 10485760
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
		K::TYPE_MEDIA_TYPE => 'application/json'
	],
	'jsonb' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
		K::TYPE_MEDIA_TYPE => 'application/json'
	],
	// 'money' => [
	// K::TYPE_DATA_TYPE => K::DATATYPE_NUMBER,
	// K::TYPE_TEXT_PATTERN => 'TBD'
	// ],
	'numeric' => [
		K::TYPE_DATA_TYPE => K::DATATYPE_NUMBER,
		K::TYPE_FLAGS => K::TYPE_FLAG_FRACTION_SCALE,
		/**
		 *
		 * @see https://www.postgresql.org/docs/13/datatype-numeric.html#DATATYPE-NUMERIC-DECIMAL
		 */
		K::TYPE_MAX_LENGTH => 1000
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
		K::TYPE_MEDIA_TYPE => 'text/xml'
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
	$mediaTypeConstants, $typePaddingDirectionConstants,
	$typeFlagConstants, $dataTypeConstants, $typePropertyConstants) {
		$cleanName = \str_replace('"', '', $name);

		$s = '';
		$s .= "'" . $cleanName . "'" . ' => new ArrayObjectType([ ' .
		PHP_EOL;
		// name
		$s .= $typePropertyConstants[K::TYPE_NAME] . " => '" . $cleanName .
		"'," . PHP_EOL;
		foreach ($properties as $k => $v)
		{
			$propertyName = $typePropertyConstants[$k];
			if ($k == K::TYPE_DATA_TYPE)
			{
				if (Container::keyExists($dataTypeConstants, $v))
					$v = Container::keyValue($dataTypeConstants, $v, $v);
				else
				{
					$a = [];
					foreach ($dataTypeConstants as $flag => $constant)
					{
						if (($v & $flag) == $flag)
							$a[] = $constant;
					}

					$v = '(' . \implode(' | ', $a) . ')';
				}
			}
			elseif ($k == K::TYPE_PADDING_DIRECTION)
				$v = $typePaddingDirectionConstants[$v];
			elseif ($k == K::TYPE_FLAGS)
			{
				$a = [];
				foreach ($typeFlagConstants as $flag => $constant)
				{
					if (($v & $flag) == $flag)
						$a[] = $constant;
				}
				if (\count($a) == 0)
					continue;

				$v = '(' . \implode(' | ', $a) . ')';
			}
			elseif ($k == K::TYPE_MEDIA_TYPE)
			{
				$v = Container::keyValue($mediaTypeConstants, $v,
					escapeshellarg($v));
			}
			else
			{
				if (\is_string($v) && !\preg_match('/K::[A-Z]/', $v))
					$v = escapeshellarg($v);
			}

			$s .= $propertyName . " => " . $v . ', ' . PHP_EOL;
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
	',(--<typeProperties>--).*?(--</typeProperties>--),sm',
	'\1 */' . PHP_EOL . $typePropertiesMapContent . PHP_EOL . '/* \2',
	$file);

file_put_contents($filename, $file);