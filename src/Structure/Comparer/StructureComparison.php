<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Comparer;

use NoreSources\Bitset;
use NoreSources\Container\Container;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\Type\StringRepresentation;
use NoreSources\Type\TypeDescription;

/**
 * Represents a difference of structure asset
 */
class StructureComparison implements StringRepresentation
{

	/**
	 * Difference type
	 *
	 * No differences
	 */
	const IDENTICAL = Bitset::BIT_01;

	/**
	 * Difference type.
	 *
	 * Element was renamed
	 */
	const RENAMED = Bitset::BIT_02;

	/**
	 * Difference type.
	 *
	 * Element was created.
	 */
	const CREATED = Bitset::BIT_03;

	/**
	 * Difference type.
	 *
	 * Element was removed.
	 */
	const DROPPED = Bitset::BIT_04;

	/**
	 * Difference type.
	 *
	 * Element was modified.
	 */
	const ALTERED = Bitset::BIT_05;

	const DIFFERENCE_TYPES = (self::ALTERED | self::CREATED |
		self::DROPPED | self::RENAMED);

	const ALL_TYPES = (self::IDENTICAL | self::DIFFERENCE_TYPES);

	/**
	 * A short hint about the difference nature
	 *
	 * @var string
	 */
	public $hint;

	public function __toString()
	{
		$s = self::getComparisonTypename($this->getType()) . ':' .
			TypeDescription::getLocalName($this->getStructure());
		if (\strlen($this->hint))
			$s .= '[' . $this->hint . ']';
		elseif (!empty($this->extras))
		{
			$list = Container::map($this->extras,
				function ($i, $e) {
					return $e[DifferenceExtra::KEY_TYPE];
				});
			$list = \array_unique($list);
			$s .= '[' . \implode(',', $list) . ']';
		}

		if (isset($this->reference))
			$s .= '<' . \strval($this->reference->getIdentifier());
		else
			$s .= '<?';
		if (isset($this->target))
			$s .= '>' . \strval($this->target->getIdentifier());
		else
			$s .= '>?';
		return $s;
	}

	public function getType()
	{
		return $this->differenceType;
	}

	/**
	 *
	 * @return StructureElementInterface
	 */
	public function getReference()
	{
		return $this->reference;
	}

	/**
	 *
	 * @return StructureElementInterface
	 */
	public function getTarget()
	{
		return $this->target;
	}

	/**
	 *
	 * @return StructureElementInterface
	 */
	public function getStructure()
	{
		if (isset($this->reference))
			return $this->reference;
		return $this->target;
	}

	/**
	 *
	 * @return DifferenceExtra[]
	 */
	public function getExtras()
	{
		if (isset($this->extras))
			return $this->extras;
		return [];
	}

	public function appendExtra(DifferenceExtra $extra)
	{
		if (!isset($this->extras))
			$this->extras = [];
		$this->extras[] = $extra;
	}

	/**
	 * Replace extra informations by the given array
	 *
	 * @param DifferenceExtra[] $extras
	 */
	public function exchangeExtras($extras)
	{
		$this->extras = [];
		foreach ($extras as $extra)
			$this->appendExtra($extra);
	}

	public function __construct($type,
		StructureElementInterface $reference = null,
		StructureElementInterface $target = null, $hint = '')
	{
		$this->differenceType = $type;
		$this->reference = $reference;
		$this->target = $target;
		$this->hint = $hint;
	}

	const FILTER_TYPE = 'type';

	const FILTER_REFERENCE = 'reference';

	const FILTER_TARGET = 'target';

	const FILTER_STRUCTURE = 'structure';

	public function match($filter = array())
	{
		foreach ($filter as $k => $v)
		{
			switch ($k)
			{
				case self::FILTER_REFERENCE:
					if ($this->getReference() != $v)
						return false;
				break;
				case self::FILTER_STRUCTURE:
					if ($this->getStructure() != $v)
						return false;
				break;
				case self::FILTER_TARGET:
					if ($this->getTarget() != $v)
						return false;
				break;
				case self::FILTER_TYPE:
					if (($this->getType() & $v) == 0)
						return false;
				break;
			}
		}
		return true;
	}

	public static function getComparisonTypename($type)
	{
		switch ($type)
		{
			case self::IDENTICAL:
				return 'identical';
			case self::ALTERED:
				return 'altered';
			case self::CREATED:
				return 'created';
			case self::DROPPED:
				return 'dropped';
			case self::RENAMED:
				return 'renamed';
		}

		return ($type) ? 'mixed' : 'none';
	}

	/**
	 *
	 * @var string
	 */
	private $differenceType;

	/**
	 *
	 * @var StructureElementInterface
	 */
	private $reference;

	/**
	 *
	 * @var StructureElementInterface
	 */
	private $target;

	/**
	 * Extran informations (optional)
	 *
	 * @var DifferenceExtra[]
	 */
	private $extras;
}
