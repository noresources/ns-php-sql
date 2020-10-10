<?php
namespace NoreSources\SQL\DBMS;

use NoreSources\Bitset;
use NoreSources\CaseInsensitiveKeyMapTrait;
use NoreSources\Container;
use NoreSources\TypeConversion;
use NoreSources\MediaType\MediaTypeInterface;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\ColumnDescriptionInterface;
use NoreSources\SQL\Structure\ColumnStructure;
use Psr\Container\ContainerInterface;

/**
 * DBMS Type registry
 */
class TypeRegistry implements \ArrayAccess, \Countable,
	ContainerInterface, \IteratorAggregate
{

	use CaseInsensitiveKeyMapTrait;

	public function matchDescription(
		ColumnDescriptionInterface $description)
	{
		$scores = [];
		$descriptionFlags = $description->getColumnProperty(
			K::COLUMN_FLAGS);
		foreach ($this as $typeKey => $type)
		{
			/**
			 *
			 * @var TypeInterface $type
			 */

			$scores[$typeKey] = 0;

			$typeFlags = self::PROPERTY_FLAGS_DEFAULT;
			if ($type->has(K::TYPE_FLAGS))
				$typeFlags = $type->get(K::TYPE_FLAGS);

			// Data type
			$dataTypeScore = 0;
			if ($description->hasColumnProperty(K::COLUMN_DATA_TYPE))
			{
				$dataType = $description->getColumnProperty(
					K::COLUMN_DATA_TYPE);
				$typeDataType = K::DATATYPE_STRING;
				if ($type->has(K::TYPE_DATA_TYPE))
					$typeDataType = $type->get(K::TYPE_DATA_TYPE);

				if ($typeDataType == $dataType) // Exact match
					$dataTypeScore = 4;
				elseif (($typeDataType & $dataType) == $dataType) // More than necessary
					$dataTypeScore = 3;
				elseif (($typeDataType & $dataType) != 0) // Partial match
				{
					if (($typeDataType & $dataType) ==
						($dataType & ~K::DATATYPE_TIMEZONE))
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

			$scores[$typeKey] += ($dataTypeScore *
				self::SCORE_MULTIPLIER_DATA_TYPE);

			if ($description->hasColumnProperty(K::COLUMN_DEFAULT_VALUE))
			{
				if (($typeFlags & K::TYPE_FLAG_DEFAULT_VALUE) == 0)
				{
					$scores[$typeKey] = -1000;
					continue;
				}
			}

			if ($descriptionFlags & K::COLUMN_FLAG_NULLABLE)
			{
				if (($typeFlags & K::TYPE_FLAG_NULLABLE) == 0)
				{
					$scores[$typeKey] = -1000;
					continue;
				}
			}

			if ($description->hasColumnProperty(K::COLUMN_LENGTH))
			{
				$length = TypeConversion::toInteger(
					$description->getColumnProperty(K::COLUMN_LENGTH));

				if ($dataType == K::DATATYPE_INTEGER &&
					$type->has(K::TYPE_SIZE))
				{
					$typeSize = $type->get(K::TYPE_SIZE);
					$columnMaxValue = \intval(\str_repeat('9', $length));
					$signed = (($descriptionFlags &
						K::COLUMN_FLAG_UNSIGNED) &&
						($typeFlags & K::TYPE_FLAG_SIGNNESS));
					$typeMaxValue = Bitset::getMaxIntegerValue(
						$typeSize, $signed);

					if ($typeMaxValue < $columnMaxValue)
					{
						$scores[$typeKey] = -1000;
						continue;
					}
				}

				$typeMaxLength = $type->getTypeMaxLength();
				if (!\is_infinite($typeMaxLength) && $typeMaxLength > 0)
				{
					if ($typeMaxLength < $length)
					{
						$scores[$typeKey] = -1000;
						continue;
					}
				} // length

				if ($description->hasColumnProperty(
					K::COLUMN_FRACTION_SCALE))
				{
					$scale = TypeConversion::toInteger(
						$description->getColumnProperty(
							K::COLUMN_FRACTION_SCALE));

					if ($scale > 0)
					{
						if (($typeFlags & K::TYPE_FLAG_FRACTION_SCALE) ==
							0)
						{
							$scores[$typeKey] = -1000;
							continue;
						}
					}
				}
			} // length

			$paddingScore = 0;
			if ($description->hasColumnProperty(
				K::COLUMN_PADDING_DIRECTION))
			{
				$cd = $description->getColumnProperty(
					K::COLUMN_PADDING_DIRECTION);
				if ($type->has(K::TYPE_PADDING_DIRECTION))
				{
					$paddingScore++;

					$td = $type->get(K::TYPE_PADDING_DIRECTION);
					if ($cd == $tc)
						$paddingScore++;

					if ($description->hasColumnProperty(
						K::COLUMN_PADDING_GLYPH))
					{
						if ($type->has(K::TYPE_PADDING_GLYPH))
						{
							if ($description->getColumnProperty(
								K::COLUMN_PADDING_GLYPH) ==
								$type->get(K::TYPE_PADDING_GLYPH))
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
				if ($type->has(K::TYPE_PADDING_DIRECTION))
					$paddingScore--;
			}

			$scores[$typeKey] += $paddingScore *
				self::SCORE_MULTIPLIER_PADDING;

			$mediaTypeScore = 0;
			if ($description->hasColumnProperty(K::COLUMN_MEDIA_TYPE))
			{
				$descriptionMediaType = $description->getColumnProperty(
					K::COLUMN_MEDIA_TYPE);
				if (!$type->has(K::TYPE_MEDIA_TYPE))
				{
					$scores[$typeKey] = -1000;
					continue;
				}

				$typeMediaType = $type->get(K::TYPE_MEDIA_TYPE);

				if (TypeConversion::toString($descriptionMediaType) ==
					TypeConversion::toString($typeMediaType))
					$mediaTypeScore += 2;
				elseif ($descriptionMediaType instanceof MediaTypeInterface &&
					$typeMediaType instanceof MediaTypeInterface)
				{
					if ($descriptionMediaType->getStructuredSyntax() ==
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
			elseif ($type->has(K::TYPE_MEDIA_TYPE))
			{
				$scores[$typeKey] = -1000;
				continue;
			}

			$scores[$typeKey] += $mediaTypeScore *
				self::SCORE_MULTIPLIER_MEDIA_TYPE;
		} // foreach

		$filtered = Container::filter($this->map,
			function ($typeKey, $type) use ($scores) {
				return $scores[$typeKey] >= 0;
			});

		if ($description instanceof ColumnStructure)
		{
			$types = $this->map;
			uksort($filtered,
				function ($ka, $kb) use ($scores, $description) {
					$a = $scores[$ka];
					$b = $scores[$kb];
					$v = ($b - $a);

					$ta = $this->get($ka);
					$tb = $this->get($kb);

					if ($v != 0)
						return $v;

					// Smallest first
					$v = self::compareTypeLength($ta, $tb);

					if (!$description->hasColumnProperty(
						K::COLUMN_LENGTH))
					{
						// Larger first
						$v = -$v;
					}

					return $v;
				});
		}

		return new TypeRegistry($filtered, [], true);
	}

	/**
	 *
	 * @param callable $callable
	 * @return \NoreSources\SQL\DBMS\TypeRegistry
	 */
	public function filter($callable)
	{
		$subset = Container::filter($this->map, $callable);
		return new TypeRegistry($subset, [], true);
	}

	/**
	 *
	 * @param TypeInterface[] $array
	 *        	Type map
	 * @param array $aliases
	 *        	Alias -> type name array
	 */
	public function __construct($array = array(), $aliases = array(),
		$safe = false)
	{
		if (!$safe)
			$array = Container::map($array,
				function ($name, $properties) {
					if (!($properties instanceof TypeInterface))
					{
						if (!Container::keyExists($properties,
							K::TYPE_NAME))
							$properties[K::TYPE_NAME] = ($name);
						$properties = new ArrayObjectType($properties);
					}
					return $properties;
				});
		$this->initializeCaseInsensitiveKeyMapTrait($array, $safe);

		foreach ($aliases as $alias => $target)
		{
			$this->offsetSet($alias, $this->get($target));
		}
	}

	/**
	 *
	 * @param TypeInterface $a
	 * @param TypeInterface $b
	 * @return number <ul>
	 *         <li>&lt; 0 if Lenght of $a is lesser than length of $b</li>
	 *         <li>&gt; 0 if Lenght of $a is greater than length of $b</li>
	 *         <li>0 if length of $a and $b are equal</li>
	 *         </ul>
	 */
	public static function compareTypeLength(TypeInterface $a,
		TypeInterface $b)
	{
		$a = $a->getTypeMaxLength();
		$b = $b->getTypeMaxLength();

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

	protected function setAlias($alias, $target)
	{
		$this->map->offsetSet($alias, $this->get($target));
	}

	const PROPERTY_FLAGS_DEFAULT = K::TYPE_FLAG_DEFAULT_VALUE |
		K::TYPE_FLAG_NULLABLE;

	const SCORE_MULTIPLIER_DATA_TYPE = 100;

	const SCORE_MULTIPLIER_PADDING = 1;

	const SCORE_MULTIPLIER_MEDIA_TYPE = 25;
}
