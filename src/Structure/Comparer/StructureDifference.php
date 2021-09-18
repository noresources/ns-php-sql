<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Comparer;

use NoreSources\Container\Container;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\Type\TypeDescription;

class StructureDifference
{

	/**
	 * Difference type.
	 *
	 * Element was renamed
	 */
	const RENAMED = 'renamed';

	/**
	 * Difference type.
	 *
	 * Element was created.
	 */
	const CREATED = 'created';

	/**
	 * Difference type.
	 *
	 * Element was removed.
	 */
	const DROPPED = 'dropped';

	/**
	 * Difference type.
	 *
	 * Element was modified.
	 */
	const ALTERED = 'altered';

	/**
	 * A short hint about the difference nature
	 *
	 * @var string
	 */
	public $hint;

	public function __toString()
	{
		$s = $this->getType() . ':' .
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
	 * @return \NoreSources\SQL\Structure\StructureElementInterface
	 */
	public function getReference()
	{
		return $this->reference;
	}

	/**
	 *
	 * @return \NoreSources\SQL\Structure\StructureElementInterface
	 */
	public function getTarget()
	{
		return $this->target;
	}

	/**
	 *
	 * @return \NoreSources\SQL\Structure\StructureElementInterface
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
					if ($this->getType() != $v)
						return false;
				break;
			}
		}
		return true;
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
