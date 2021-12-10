<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\Bitset;
use NoreSources\SingletonTrait;
use NoreSources\SQL\Constants as K;

class DataTypeDescription
{
	use SingletonTrait;

	/**
	 * Data types both have the DATATYPE_NULL affinity or
	 * none of them.
	 */
	const AFFINITY_MATCH_NULL = Bitset::BIT_01;

	/**
	 * Data types share at least one non-DATATYPE_NULL affinity
	 */
	const AFFINITY_MATCH_ONE = Bitset::BIT_02;

	/**
	 * Data types have the same non-DATATYPE_NULL affinities.
	 */
	const AFFINITY_MATCH_STRICT = Bitset::BIT_03;

	const AFFINITY_MATCH_ALL = self::AFFINITY_MATCH_NULL |
		self::AFFINITY_MATCH_ONE | self::AFFINITY_MATCH_STRICT;

	public function compareAffinity($a, $b)
	{
		$flags = self::AFFINITY_MATCH_NULL | self::AFFINITY_MATCH_STRICT;

		foreach ([
			Constants::DATATYPE_BINARY,
			Constants::DATATYPE_BOOLEAN,
			Constants::DATATYPE_NUMBER,
			Constants::DATATYPE_TIMESTAMP,
			Constants::DATATYPE_NULL
		] as $affinity)
		{

			$ba = \boolval($a & $affinity);
			$bb = \boolval($b & $affinity);

			if ($affinity == Constants::DATATYPE_NULL)
			{
				if ($ba != $bb)
					$flags &= ~self::AFFINITY_MATCH_NULL;
			}

			elseif ($ba != $bb)
				$flags &= ~self::AFFINITY_MATCH_STRICT;
			elseif ($ba && $bb)
				$flags |= self::AFFINITY_MATCH_ONE;
		}

		return $flags;
	}

	public function getAffinities($dataType)
	{
		$descriptions = $this->getDescriptions();
		$affinities = [];
		foreach ($descriptions as $description)
		{
			if ($dataType & $description[self::AFFINITY])
				$affinities[] = $description[self::AFFINITY];
		}
		return \array_unique($affinities);
	}

	/**
	 * Get the data type integer value from the data type name
	 *
	 * @param string $dataTypeName
	 *        	Data type name
	 * @return integer
	 */
	public function getDataTypeNameDataType($dataTypeName)
	{
		$descriptions = $this->getDescriptions();
		foreach ($descriptions as $description)
		{
			if (\strcasecmp($description[2], $dataTypeName) == 0)
				return $description[1];
		}

		return K::DATATYPE_UNDEFINED;
	}

	public function getNames($dataType)
	{
		$descriptions = $this->getDescriptions();
		$names = [];
		foreach ($descriptions as $description)
		{
			if (($dataType & $description[self::AFFINITY]) ==
				$description[self::TYPE])
				$names[] = $description[self::NAME];
		}
		return $names;
	}

	public function getAvailableDataTypeNames()
	{
		$descriptions = $this->getDescriptions();
		$names = [];
		foreach ($descriptions as $d)
			$names[] = $d[2];
		return $names;
	}

	const AFFINITY = 0;

	const TYPE = 1;

	const NAME = 2;

	private function getDescriptions()
	{
		if (!isset($this->dataTypeDescriptions))
		{
			$this->dataTypeDescriptions = new \ArrayObject(
				[
					[
						Constants::DATATYPE_BINARY,
						Constants::DATATYPE_BINARY,
						'binary'
					],
					[
						Constants::DATATYPE_BOOLEAN,
						Constants::DATATYPE_BOOLEAN,
						'boolean'
					],
					[
						Constants::DATATYPE_TIMESTAMP,
						Constants::DATATYPE_DATE,
						'date'
					],
					[
						Constants::DATATYPE_TIMESTAMP,
						Constants::DATATYPE_TIME,
						'time'
					],
					[
						Constants::DATATYPE_TIMESTAMP,
						Constants::DATATYPE_DATETIME,
						'datetime'
					],
					[
						Constants::DATATYPE_TIMESTAMP,
						Constants::DATATYPE_TIMESTAMP,
						'timestamp'
					],
					[
						Constants::DATATYPE_NULL,
						Constants::DATATYPE_NULL,
						'null'
					],
					[

						Constants::DATATYPE_NUMBER,
						Constants::DATATYPE_INTEGER,
						'integer'
					],
					[

						Constants::DATATYPE_NUMBER,
						Constants::DATATYPE_REAL,
						'float'
					],
					[
						Constants::DATATYPE_NUMBER,
						Constants::DATATYPE_NUMBER,
						'number'
					],
					[
						Constants::DATATYPE_STRING,
						Constants::DATATYPE_STRING,
						'string'
					]
				]);
		}

		return $this->dataTypeDescriptions;
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $dataTypeDescriptions;
}
