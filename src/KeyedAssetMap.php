<?php

/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\Container\ArrayAccessContainerInterfaceTrait;
use NoreSources\Container\CaseInsensitiveKeyMapTrait;
use NoreSources\Container\Container;

/**
 * Map of elements with case-insensitive keys
 */
class KeyedAssetMap implements AssetMapInterface, \ArrayAccess
{
	use CaseInsensitiveKeyMapTrait;
	use ArrayAccessContainerInterfaceTrait;

	public function __construct($map = array())
	{
		$this->initializeCaseInsensitiveKeyMapTrait($map);
	}

	public function append($value)
	{
		$this->offsetSet(null, $value);
	}

	public function offsetExists($id)
	{
		if (\is_integer($id))
			return ($id >= 0 && $id < $this->count());
		return $this->caselessOffsetExists($id);
	}

	public function offsetGet($id)
	{
		if (\is_integer($id))
		{
			if ($id >= 0 && $id < $this->count())
				return Container::nthValue($this, $id);

			$this->onKeyNotFound($id);
		}

		return $this->caselessOffsetGet($id);
	}

	public function offsetSet($name, $value)
	{
		if (!\is_string($name) &&
			($value instanceof NameProviderInterface))
			$name = $value->getName();
		if (!\is_string($name))
			throw new \InvalidArgumentException(
				$name . ' is not a string');

		$this->caselessOffsetSet($name, $value);
	}
}
