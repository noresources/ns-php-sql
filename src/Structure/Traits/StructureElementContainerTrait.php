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

use NoreSources\CaseInsensitiveKeyMapTrait;
use NoreSources\Container;
use NoreSources\SQL\NameProviderInterface;
use NoreSources\SQL\Structure\StructureElementInterface;

trait StructureElementContainerTrait
{

	use CaseInsensitiveKeyMapTrait;

	public function getChildElements($typeFilter = null)
	{
		if ($typeFilter)
			return Container::filter($this->map,
				function ($n, $e) use ($typeFilter) {
					return (is_a($e, $typeFilter));
				});
		return $this->map->getArrayCopy();
	}

	public function appendElement(StructureElementInterface $child)
	{
		$key = $child->getElementKey();
		$child->setParentElement($this);
		$this->map->offsetSet($key, $child);

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

	public function offsetUnset($key)
	{
		if ($key instanceof NameProviderInterface)
			$key = $key->getElementKey();
		if ($this->offsetExists($key))
		{
			$e = $this->map[$key];
			$this->map->offsetUnset($key);

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

		if (!($value instanceof StructureElementInterface))
			throw new \InvalidArgumentException(
				'Invalid value argument. ' . StructureElement::class .
				' expected.');

		if (\strcasecmp($key, $value->getElementKey()))
			throw new \InvalidArgumentException(
				'Key & value mismatch. Key must be the element name');

		$value->setParentElement($this);
		$this->map->offsetSet($key, $value);
	}

	protected function cloneStructureElementContainer()
	{
		foreach ($this->map as $key => $value)
		{
			$e = clone $value;
			$e->setParentElement($this);
			$this->map->offsetSet($key, $e);
		}
	}

	protected function initializeStructureElementContainer()
	{
		$this->map = new \ArrayObject([]);
	}
}