<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Comparer;

use NoreSources\SQL\Structure\StructureElementInterface;

class StructureDifference
{

	const RENAMED = 'renamed';

	const CREATED = 'created';

	const DROPPED = 'dropped';

	const ALTERED = 'altered';

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

	public function __construct($type,
		StructureElementInterface $reference = null,
		StructureElementInterface $target = null)
	{
		$this->differenceType = $type;
		$this->reference = $reference;
		$this->target = $target;
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
}
