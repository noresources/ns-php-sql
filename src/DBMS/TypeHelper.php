<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\Container;
use NoreSources\TypeConversion;
use NoreSources\MediaType\MediaTypeInterface;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\ColumnStructure;

class TypeHelper
{

	/**
	 *
	 * @param ColumnStructure $column
	 * @param TypeInterface[] $types
	 */
	public static function getMatchingTypes(ColumnStructure $column, $types)
	{
		$scores = [];
		$columnFlags = $column->getColumnProperty(K::COLUMN_PROPERTY_FLAGS);
		foreach ($types as $typeKey => $type)
		{
			$scores[$typeKey] = 0;

			$typeFlags = self::getProperty($type, K::TYPE_PROPERTY_FLAGS);

			// Data type
			$dataTypeScore = 0;
			if ($column->hasColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE))
			{
				$dataType = $column->getColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE);
				$typeDataType = self::getProperty($type, K::TYPE_PROPERTY_DATA_TYPE);

				if ($typeDataType == $dataType) // Exact match
					$dataTypeScore = 4;
				elseif (($typeDataType & $dataType) == $dataType) // More than necessary
					$dataTypeScore = 3;
				elseif (($typeDataType & $dataType) != 0) // Partial match
				{
					if (($typeDataType & $dataType) == ($dataType & ~K::DATATYPE_TIMEZONE))
						$dataTypeScore = 2;
					else
						$dataTypeScore = 1;
				}
				else
				{
					$scores[$typeKey] = -1000;
					continue;
				}
			}

			$scores[$typeKey] += ($dataTypeScore * self::SCORE_MULTIPLIER_DATA_TYPE);

			if ($column->hasColumnProperty(K::COLUMN_PROPERTY_DEFAULT_VALUE))
			{
				if (($typeFlags & K::TYPE_FLAG_DEFAULT_VALUE) == 0)
				{
					$scores[$typeKey] = -1000;
					continue;
				}
			}

			if ($columnFlags & K::COLUMN_FLAG_ACCEPT_NULL)
			{
				if (($typeFlags & K::TYPE_FLAG_NULL) == 0)
				{
					$scores[$typeKey] = -1000;
					continue;
				}
			}

			if ($column->hasColumnProperty(K::COLUMN_PROPERTY_LENGTH))
			{
				$length = TypeConversion::toInteger(
					$column->getColumnProperty(K::COLUMN_PROPERTY_LENGTH));

				$typeLength = self::getMaxLength($type);
				if ($typeLength > 0)
				{
					if ($typeLength < $length)
					{
						$scores[$typeKey] = -1000;
						continue;
					}
				}

				if ($column->hasColumnProperty(K::COLUMN_PROPERTY_FRACTION_SCALE))
				{
					$scale = TypeConversion::toInteger(
						$column->getColumnProperty(K::COLUMN_PROPERTY_FRACTION_SCALE));

					if ($scale > 0)
					{
						if (($typeFlags & K::TYPE_FLAG_FRACTION_SCALE) == 0)
						{
							$scores[$typeKey] = -1000;
							continue;
						}
					}
				} // scale
			} // length

			$paddingScore = 0;
			if ($column->hasColumnProperty(K::COLUMN_PROPERTY_PADDING_DIRECTION))
			{
				$cd = $column->getColumnProperty(K::COLUMN_PROPERTY_PADDING_DIRECTION);
				if ($type->has(K::TYPE_PROPERTY_PADDING_DIRECTION))
				{
					$paddingScore++;

					$td = $type->get(K::TYPE_PROPERTY_PADDING_DIRECTION);
					if ($cd == $tc)
						$paddingScore++;

					if ($column->hasColumnProperty(K::COLUMN_PROPERTY_PADDING_GLYPH))
					{
						if ($type->has(K::TYPE_PROPERTY_PADDING_GLYPH))
						{
							if ($column->getColumnProperty(K::COLUMN_PROPERTY_PADDING_GLYPH) ==
								$type->get(K::TYPE_PROPERTY_PADDING_GLYPH))
								$paddingScore++;
							else
								$paddingScore--;
						}
					}
				}
				else
					$paddingScore--;
			}
			else
			{
				if ($type->has(K::TYPE_PROPERTY_PADDING_DIRECTION))
					$paddingScore--;
			}

			$scores[$typeKey] += $paddingScore * self::SCORE_MULTIPLIER_PADDING;

			$mediaTypeScore = 0;
			if ($column->hasColumnProperty(K::COLUMN_PROPERTY_MEDIA_TYPE))
			{
				$columnMediaType = $column->getColumnProperty(K::COLUMN_PROPERTY_MEDIA_TYPE);
				if (!$type->has(K::TYPE_PROPERTY_MEDIA_TYPE))
				{
					$scores[$typeKey] = -1000;
					continue;
				}

				$typeMediaType = $type->get(K::TYPE_PROPERTY_MEDIA_TYPE);

				if (TypeConversion::toString($columnMediaType) ==
					TypeConversion::toString($typeMediaType))
					$mediaTypeScore += 2;
				elseif ($columnMediaType instanceof MediaTypeInterface &&
					$typeMediaType instanceof MediaTypeInterface)
				{
					if ($columnMediaType->getStructuredSyntax() ==
						$typeMediaType->getStructuredSyntax())
						$mediaTypeScore += 1;
					else // type mismatch
					{
						$scores[$typeKey] = -1000;
						continue;
					}
				}
				else
				{
					$scores[$typeKey] = -1000;
					continue;
				}
			}
			elseif ($type->has(K::TYPE_PROPERTY_MEDIA_TYPE))
			{
				$scores[$typeKey] = -1000;
				continue;
			}
		} // foreach

		$scores[$typeKey] += $mediaTypeScore * self::SCORE_MULTIPLIER_MEDIA_TYPE;

		$filtered = Container::filter($types,
			function ($typeKey, $type) use ($scores) {
				return $scores[$typeKey] >= 0;
			});

		uksort($filtered,
			function ($ka, $kb) use ($scores, $types, $column) {
				$a = $scores[$ka];
				$b = $scores[$kb];
				$v = ($b - $a);

				$ta = $types[$ka];
				$tb = $types[$kb];

				if ($v != 0)
					return $v;

				// Smallest first
				$v = self::compareTypeLength($ta, $tb);

				if (!$column->hasColumnProperty(K::COLUMN_PROPERTY_LENGTH))
				{
					// Larger first
					$v = -$v;
				}

				return $v;
			});

		return $filtered;
	}

	public static function compareTypeLength(TypeInterface $a, TypeInterface $b)
	{
		$a = self::getMaxLength($a);
		$b = self::getMaxLength($b);

		if (\is_infinite($a))
		{
			if (\is_infinite($b))
				return 0;

			return 1;
		}
		elseif (\is_infinite($b))
			return -1;

		return ($a - $b);
	}

	/**
	 *
	 * @param TypeInterface $type
	 * @param string $property
	 * @throws TypeException
	 * @return mixed Type property or default value for this property (if any).
	 */
	public static function getProperty(TypeInterface $type, $property)
	{
		if ($type->has($property))
			return $type->get($property);

		$dflt = self::getDefaultTypeProperties();
		if (Container::keyExists($dflt, $property))
			return $dflt[$property];

		throw new TypeException($type, 'Property ' . $property . ' not available');
	}

	public static function getMaxLength(TypeInterface $type)
	{
		if ($type->has(K::TYPE_PROPERTY_MAX_LENGTH))
			return $type->get(K::TYPE_PROPERTY_MAX_LENGTH);

		if ($type->has(K::TYPE_PROPERTY_DATA_TYPE))
		{
			$dataType = $type->get(K::TYPE_PROPERTY_DATA_TYPE);
			if ($dataType & K::DATATYPE_NUMBER)
				return self::getIntegerTypeMaxLength(
					Container::keyValue($type, K::TYPE_PROPERTY_SIZE, 0));
			elseif ($dataType == K::DATATYPE_STRING)
			{
				if ($type->has(K::TYPE_PROPERTY_SIZE))
					return intval($type->get(K::TYPE_PROPERTY_SIZE) / 8);
			}
		}

		return INF;
	}

	/**
	 *
	 * @param integer $typeSize
	 *        	Type size in bits
	 * @return mixed|mixed|array|\ArrayAccess|\Psr\Container\ContainerInterface|\Traversable|number
	 */
	public static function getIntegerTypeMaxLength($typeSize)
	{
		if (!\is_array(self::$typeMaxGlyphCount))
			self::$typeMaxGlyphCount = [];

		if ($typeSize == INF || $typeSize == 0)
			return INF;

		if (\array_key_exists($typeSize, self::$typeMaxGlyphCount))
			return self::$typeMaxGlyphCount[$typeSize];

		$maxValue = pow(2, $typeSize) - 1;
		$max = 1;
		while ($maxValue > 10)
		{
			$maxValue /= 10;
			$max++;
		}

		return $max;
	}

	public static function getDefaultTypeProperties()
	{
		if (!(self::$typeDefaultProperties instanceof \ArrayObject))
		{
			self::$typeDefaultProperties = new \ArrayObject(
				[
					K::TYPE_PROPERTY_DATA_TYPE => K::DATATYPE_STRING,
					K::TYPE_PROPERTY_FLAGS => K::TYPE_FLAG_NULL | K::TYPE_FLAG_DEFAULT_VALUE
				]);
		}

		return self::$typeDefaultProperties;
	}

	private static $typeMaxGlyphCount;

	/**
	 *
	 * @var \ArrayObject
	 */
	private static $typeDefaultProperties;

	const SCORE_MULTIPLIER_DATA_TYPE = 100;

	const SCORE_MULTIPLIER_PADDING = 1;

	const SCORE_MULTIPLIER_MEDIA_TYPE = 25;
}