<?php
/**
 * Copyright © 2012 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Traits;

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
		return Identifier::make($this);
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

	public function detachElement()
	{
		$p = $this->getParentElement();
		$this->parentElement = null;
		if ($p instanceof ArrayAccess)
			$p->offsetUnset($this->getElementKey());
	}

	public function setParentElement(
		StructureElementInterface $parent = null)
	{
		$this->parentElement = $parent;
	}

	/**
	 *
	 * @param string $name
	 */
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
	{
		$this->parentElement = null;
		$this->elementKey = Identifier::generate();
	}

	/**
	 *
	 * @var string
	 */
	protected $elementName;

	/**
	 *
	 * @var StructureElementInterface
	 */
	protected $parentElement;

	/**
	 *
	 * @var string
	 */
	private $elementKey;
}