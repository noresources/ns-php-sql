<?php

/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Traits;

use NoreSources\SQL\Structure\StructureElementInterface;

trait StructureElementContainerTrait
{

	public function getChildElements()
	{
		return $this->childElements->getArrayCopy();
	}

	public function appendElement(StructureElementInterface $child)
	{
		$key = $child->getName();
		$child->setParentElement($this);
		$this->childElements->offsetSet($key, $child);

		return $child;
	}

	public function findDescendant($tree)
	{
		if (is_string($tree))
			return $this->offsetGet($tree);

		$e = $this;
		foreach ($tree as $key)
		{
			$e = $e->offsetGet($key);
			if (!$e)
				break;
		}

		return $e;
	}

	public function count()
	{
		return $this->childElements->count();
	}

	/**
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return $this->childElements->getIterator();
	}

	/**
	 *
	 * @param
	 *        	string Element name
	 * @return StructureElement
	 */
	public function offsetGet($key)
	{
		if ($this->childElements->offsetExists($key))
		{
			return $this->childElements->offsetGet($key);
		}

		$key = strtolower($key);
		if ($this->childElements->offsetExists($key))
		{
			return $this->childElements->offsetGet($key);
		}

		return null;
	}

	public function offsetUnset($key)
	{
		if ($key instanceof StructureElementInterface)
			$key = $key->getName();
		$key = \strtolower($key);
		if ($this->offsetExists($key))
		{
			$e = $this->childElements[$key];
			$this->childElements->offsetUnset($key);

			if ($e instanceof StructureElementInterface)
				if ($e->getParentElement() == $this)
					$e->detachElement();
		}
	}

	public function offsetSet($key, $value)
	{
		if (\is_string($key))
			throw new \InvalidArgumentException(
				'Invalid key argument. string expected');

		$key = \strtolower($key);

		if (!($value instanceof StructureElementInterface))
			throw new \InvalidArgumentException(
				'Invalid value argument. ' . StructureElement::class .
				' expected.');

		$k = \strtolower($value->getName());

		if ($k != $key)
			throw new \InvalidArgumentException(
				'Key & value mismatch. Key must be the element name');

		$value->setParentElement($this);
		$this->childElements->offsetSet($key, $value);
	}

	public function offsetExists($a_key)
	{
		return $this->childElements->offsetExists($a_key);
	}

	protected function cloneStructureElementContainer()
	{
		foreach ($this->childElements as $key => $value)
		{
			$e = clone $value;
			$e->setParentElement($this);
			$this->childElements->offsetSet($key, $e);
		}
	}

	protected function initializeStructureElementContainer()
	{
		$this->childElements = new \ArrayObject([]);
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $childElements;
}