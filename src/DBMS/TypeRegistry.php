<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\Bitset;
use NoreSources\Container\ArrayAccessContainerInterfaceTrait;
use NoreSources\Container\CaseInsensitiveKeyMapTrait;
use NoreSources\Container\Container;
use NoreSources\MediaType\MediaTypeInterface;
use NoreSources\SQL\AssetMapInterface;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataDescription;
use NoreSources\SQL\DBMS\Types\ArrayObjectType;
use NoreSources\Type\TypeConversion;

/**
 * DBMS Type registry
 */
class TypeRegistry implements \ArrayAccess, AssetMapInterface
{

	use CaseInsensitiveKeyMapTrait;
	use ArrayAccessContainerInterfaceTrait;

	public function matchDescription($columnDescription)
	{
		$scores = [];
		$descriptionFlags = Container::keyValue($columnDescription,
			K::COLUMN_FLAGS);
		foreach ($this as $typeKey => $type)
		{
			/**
			 *
			 * @var TypeInterface $type
			 */

			$scores[$typeKey] = 0;

			$typeFlags = 0;
			if ($type->has(K::TYPE_FLAGS))
				$typeFlags = $type->get(K::TYPE_FLAGS);

			// Data type
			$targetDataTypeScore = 0;
			if (Container::keyExists($columnDescription,
				K::COLUMN_DATA_TYPE))
			{
				$targetDataType = Container::keyValue(
					$columnDescription, K::COLUMN_DATA_TYPE) &
					~K::DATATYPE_NULL;
				$typeDataType = K::DATATYPE_STRING;
				if ($type->has(K::TYPE_DATA_TYPE))
					$typeDataType = $type->get(K::TYPE_DATA_TYPE);

				if ($typeDataType == $targetDataType) // Exact match
					$targetDataTypeScore = 4;
				elseif (($typeDataType & $targetDataType) ==
					$targetDataType) // More than necessary
					$targetDataTypeScore = 3;
				elseif (($typeDataType & $targetDataType) != 0) // Partial match
				{
					if (($typeDataType & $targetDataType) ==
						($targetDataType & ~K::DATATYPE_TIMEZONE))
						$targetDataTypeScore = 2;
					else
						$targetDataTypeScore = 1;
				}
				else
				{
					$scores[$typeKey] = -1000;
					continue;
				}
			}

			if ($targetDataTypeScore &&
				Container::keyExists($columnDescription,
					K::COLUMN_LENGTH))
			{
				// Reduce score if type does not accept length
				if (($typeFlags & K::TYPE_FLAG_LENGTH) !=
					K::TYPE_FLAG_LENGTH)
				{
					$targetDataTypeScore = max(1,
						$targetDataTypeScore - 2);
				}

				$length = TypeConversion::toInteger(
					Container::keyValue($columnDescription,
						K::COLUMN_LENGTH));

				if ($targetDataType == K::DATATYPE_INTEGER &&
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
			} // length

			// Fraction scale

			if ($targetDataType & K::DATATYPE_REAL)
			{
				if (Container::keyExists($columnDescription,
					K::COLUMN_FRACTION_SCALE) &&
					($scale = TypeConversion::toInteger(
						Container::keyValue($columnDescription,
							K::COLUMN_FRACTION_SCALE))) && ($scale > 0))
				{
					if (($typeFlags & K::TYPE_FLAG_FRACTION_SCALE) == 0)
					{
						$scores[$typeKey] = -1000;
						continue;
					}
				}
				else
				{
					if ($type->has(K::TYPE_DEFAULT_SCALE) &&
						($type->get(K::TYPE_DEFAULT_SCALE) == 0))
					{
						$scores[$typeKey] = -1000;
						continue;
					}
				}
			}

			$scores[$typeKey] += ($targetDataTypeScore *
				self::SCORE_MULTIPLIER_DATA_TYPE);

			// Default value
			if (Container::keyExists($columnDescription,
				K::COLUMN_DEFAULT_VALUE))
			{
				$defaultValue = Container::keyValue($columnDescription,
					K::COLUMN_DEFAULT_VALUE);
				$defaultValueDataType = DataDescription::getInstance()->getDataType(
					$defaultValue);

				if (!$type->acceptDefaultValue($defaultValueDataType))
				{
					$scores[$typeKey] = -1000;
					continue;
				}
			}
			// Padding

			$paddingScore = 0;
			if (Container::keyExists($columnDescription,
				K::COLUMN_PADDING_DIRECTION))
			{
				$cd = Container::keyValue($columnDescription,
					K::COLUMN_PADDING_DIRECTION);
				if ($type->has(K::TYPE_PADDING_DIRECTION))
				{
					$paddingScore++;

					$td = $type->get(K::TYPE_PADDING_DIRECTION);
					if ($cd == $tc)
						$paddingScore++;

					if (Container::keyExists($columnDescription,
						K::COLUMN_PADDING_GLYPH))
					{
						if ($type->has(K::TYPE_PADDING_GLYPH))
						{
							if (Container::keyValue($columnDescription,
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
				if ($type->has(K::TYPE_PADDING_GLYPH))
					$paddingScore--;
			}

			$scores[$typeKey] += $paddingScore *
				self::SCORE_MULTIPLIER_PADDING;

			$mediaTypeScore = 0;
			if (($descriptionMediaType = Container::keyValue(
				$columnDescription, K::COLUMN_MEDIA_TYPE)))
			{
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

		$filtered = Container::filter($this,
			function ($typeKey, $type) use ($scores) {
				return $scores[$typeKey] >= 0;
			});

		$types = $this;
		Container::uksort($filtered,
			function ($ka, $kb) use ($scores, $columnDescription) {
				$a = $scores[$ka];
				$b = $scores[$kb];
				$v = ($b - $a);

				if ($v != 0)
					return $v;

				$ta = $this->get($ka);
				$tb = $this->get($kb);

				$length = 0;
				if (Container::keyExists($columnDescription,
					K::COLUMN_LENGTH))
				{
					$length = Container::keyValue($columnDescription,
						K::COLUMN_LENGTH);
				}

				if ($length > 1)
				{
					// Type accepting length first
					$taf = Container::keyValue($ta, K::TYPE_FLAGS, 0);
					$tbf = Container::keyValue($tb, K::TYPE_FLAGS, 0);
					if ($taf & K::TYPE_FLAG_LENGTH)
					{
						if (!($tbf & K::TYPE_FLAG_LENGTH))
							return -1;
					}
					elseif ($tbf & K::TYPE_FLAG_LENGTH)
						return 1;
				}

				// Smallest first
				$v = self::compareTypeLength($ta, $tb);

				if ($length == 0) // no length specification
				{
					// Larger first
					$v = -$v;
				}

				if ($v)
					return $v;

				// name comparison for consistency.
				// Not really mandatory

				$v = \strlen($kb) - \strlen($ka);
				if ($v)
					return $v;

				return \strcmp($ka, $kb);
			});

		return new TypeRegistry($filtered, [], true);
	}

	/**
	 *
	 * @param callable $callable
	 * @return \NoreSources\SQL\DBMS\TypeRegistry
	 */
	public function filter($callable)
	{
		$subset = Container::filter($this, $callable);
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
		$this->offsetSet($alias, $this->get($target));
	}

	const SCORE_MULTIPLIER_DATA_TYPE = 100;

	const SCORE_MULTIPLIER_PADDING = 1;

	const SCORE_MULTIPLIER_MEDIA_TYPE = 25;
}
