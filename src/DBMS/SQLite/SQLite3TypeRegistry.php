<?php
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\SingletonTrait;
use NoreSources\MediaType\MediaType;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\TypeRegistry;
use NoreSources\SQL\DBMS\Types\ArrayObjectType;

/**
 * SQLIte type registry
 *
 * @see https://www.sqlite.org/datatype3.html
 */
class SQLite3TypeRegistry extends TypeRegistry
{

	use SingletonTrait;

	/**
	 * Guess type affinity from type name following SQLite rules
	 *
	 * @param string $typename
	 * @return integer Data type (combination of DATATYPE_* constants)
	 */
	public function getDataTypeFromTypename($typename, $extension = true)
	{
		$rules = [
			// Rule 1
			'INT' => (K::DATATYPE_INTEGER),
			// Rule 2
			'CLOB' => (K::DATATYPE_STRING),
			'CHAR' => (K::DATATYPE_STRING),
			'TEXT' => (K::DATATYPE_STRING),
			// Rule 3
			'BLOB' => (K::DATATYPE_BINARY),
			// Rule 4
			'FLOA' => (K::DATATYPE_NUMBER),
			'DOUB' => (K::DATATYPE_NUMBER),
			'REAL' => (K::DATATYPE_NUMBER)
		];

		if ($extension)
			$rules = \array_merge(
				[
					'BOOL' => (K::DATATYPE_BOOLEAN),
					'TIMESTAMP' => (K::DATATYPE_TIMESTAMP),
					'DATETIME' => (K::DATATYPE_DATETIME),
					'DATE' => (K::DATATYPE_DATE),
					'TIME' => (K::DATATYPE_TIME)
				], $rules);

		foreach ($rules as $pattern => $dataType)
		{
			if (\stripos($typename, $pattern) !== false)
				return $dataType;
		}

		// Rule 5
		return (K::DATATYPE_NUMBER | K::DATATYPE_STRING |
			K::DATATYPE_BINARY);
	}

	public function __construct()
	{
		parent::__construct(
			[
				// Affinity types
				'blob' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'BLOB',
						K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
						K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH)
					]),
				'integer' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'INTEGER',
						K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER |
						K::DATATYPE_BOOLEAN,
						K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
						K::TYPE_FLAG_SIGNNESS)
					]),
				'real' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'REAL',
						/**
						 * Arbitraty value to force a decent indicative value
						 * for columns with a scele specification and no length specification.
						 *
						 * Use +/- the precision of a flaot in PHP
						 *
						 * @see https://www.php.net/manual/en/language.types.float.php The size of a
						 *      float is platform-dependent, although a maximum of
						 *      approximately 1.8e308 with a precision of roughly 14 decimal digits
						 *      is a
						 *      common value (the 64 bit IEEE format).
						 */
						K::TYPE_MAX_LENGTH => 16,
						K::TYPE_DATA_TYPE => K::DATATYPE_REAL,
						K::TYPE_FLAGS => K::TYPE_FLAG_FRACTION_SCALE |
						K::TYPE_FLAG_LENGTH
					]),

				'text' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'TEXT',
						K::TYPE_DATA_TYPE => K::DATATYPE_TIMESTAMP |
						K::DATATYPE_STRING,
						K::TYPE_FLAGS => K::TYPE_FLAG_LENGTH
					]),
				// Extensions
				'boolean' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'BOOLEAN',
						K::TYPE_DATA_TYPE => K::DATATYPE_BOOLEAN
					]),
				/**
				 * According to rule 2.
				 * SQLite will consider this type
				 * as TEXT affinity. This is important to use
				 * this type with strftime('', cast(v as timestamptext))
				 */
				'timestamptext' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'TIMESTAMPTEXT',
						K::TYPE_DATA_TYPE => K::DATATYPE_TIMESTAMP
					]),
				'datetimetext' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'DATETIMETEXT',
						K::TYPE_DATA_TYPE => K::DATATYPE_DATETIME
					]),
				'datetext' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'DATETEXT',
						K::TYPE_DATA_TYPE => K::DATATYPE_DATE
					]),
				'timetext' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'TIMETEXT',
						K::TYPE_DATA_TYPE => K::DATATYPE_TIME
					]),
				// Media types specific
				'json' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'JSON',
						K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
						K::TYPE_MEDIA_TYPE => MediaType::createFromString(
							'application/json')
					])
			], [
				'numeric' => 'real'
			]);
	}
}
