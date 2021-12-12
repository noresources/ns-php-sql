<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Traits;

use NoreSources\Container\ArrayAccessContainerInterfaceTrait;
use NoreSources\Container\CaseInsensitiveKeyMapTrait;
use NoreSources\Container\Container;
use NoreSources\Container\KeyNotFoundException;
use NoreSources\SQL\NameProviderInterface;
use NoreSources\SQL\Structure\StructureElementInterface;

trait StructureElementContainerTrait
{

	use CaseInsensitiveKeyMapTrait;
	use ArrayAccessContainerInterfaceTrait;

	public function getChildElements($typeFilter = null)
	{
		if ($typeFilter)
			return Container::filter($this,
				function ($n, $e) use ($typeFilter) {
					return (is_a($e, $typeFilter));
				});
		return $this->getArrayCopy();
	}

	public function appendElement(StructureElementInterface $child)
	{
		$key = $child->getElementKey();
		$child->setParentElement($this);
		$this->caselessOffsetSet($key, $child);

		return $child;
	}

	public function findDescendant($tree)
	{
		if (is_string($tree))
			return $this->offsetGet($tree);

		$e = $this;
		foreach ($tree as $key)
		{
			if (!$e->offsetExists($key))
				return null;
			$e = $e->offsetGet($key);
		}

		return $e;
	}

	public function offsetUnset($key)
	{
		if ($key instanceof StructureElementInterface)
			$key = $key->getElementKey();
		elseif ($key instanceof NameProviderInterface)
			$key = $key->getName();

		if (!$this->offsetExists($key))
			throw new KeyNotFoundException($key);

		/** @var StructureElementInterface */
		$e = $this->offsetGet($key);

		if (($e->getParentElement() == $this))
			$e->detachElement();
		else
			$this->caselessOffsetUnset($key);
	}

	public function offsetSet($key, $value)
	{
		if (!($value instanceof StructureElementInterface))
			throw new \InvalidArgumentException(
				'Invalid value argument. ' .
				StructureElementInterface::class . ' expected.');

		if ($key !== null)
			throw new \BadMethodCallException('Key sould be NULL');

		$this->appendElement($value);
	}

	protected function cloneStructureElementContainer()
	{
		$map = $this->getArrayCopy();
		$this->initializeCaseInsensitiveKeyMapTrait(new \ArrayObject(),
			false);
		foreach ($map as $key => $value)
			$this->appendElement(clone $value);
	}

	protected function initializeStructureElementContainer()
	{
		$this->initializeCaseInsensitiveKeyMapTrait();
	}
}