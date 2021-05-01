<?php

/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Traits;

use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\StructureElementContainerInterface;
use NoreSources\SQL\Structure\StructureElementInterface;
use ArrayAccess;

trait StructureElementTrait
{

	public function getName()
	{
		return $this->elementName;
	}

	public function getIdentifier()
	{
		if (empty($this->elementName))
			return Identifier::make(null);
		$a = [
			$this->getName()
		];
		$p = $this;
		while (($p = $p->getParentElement()) &&
			!($p instanceof DatasourceStructure))
		{
			array_unshift($a, $p->getName());
		}

		return Identifier::make($a);
	}

	public function getParentElement($depth = 1)
	{
		$p = $this->parentElement;
		while ($p && ($depth > 1))
		{
			$p = $p->parentElement;
			$depth--;
		}

		return $p;
	}

	public function getRootElement()
	{
		$res = $this;
		while ($res->getParentElement())
		{
			$res = $res->getParentElement();
		}

		return $res;
	}

	public function detachElement()
	{
		$p = $this->getParentElement();
		$this->parentElement = null;
		if ($p instanceof ArrayAccess)
			$p->offsetUnset($this->getName());
	}

	public function setParentElement(
		StructureElementInterface $parent = null)
	{
		$this->parentElement = $parent;
	}

	public function setName($name)
	{
		$p = $this->getParentElement();
		if ($p instanceof StructureElementContainerInterface)
			$this->detachElement();
		$this->elementName = $name;
		if ($p instanceof StructureElementContainerInterface)
			$p->appendElement($this);
	}

	public function getElementKey()
	{
		return (empty($this->elementName) ? $this->elementKey : $this->elementName);
	}

	protected function initializeStructureElement($name,
		StructureElementContainerInterface $parent = null)
	{
		$this->elementKey = Identifier::generate();
		$this->elementName = $name;
		$this->parentElement = $parent;
	}

	protected function cloneStructureElement()
	{}

	/**
	 *
	 * @var string
	 */
	protected $elementName;

	/**
	 *
	 * @var StructureElement
	 */
	protected $parentElement;

	/**
	 *
	 * @var string
	 */
	private $elementKey;
}