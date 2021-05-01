<?php

/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\KeyNotFoundException;
use NoreSources\TypeDescription;

class IndexedAssetMap extends \ArrayObject implements AssetMapInterface
{

	public function __construct($map = array())
	{
		parent::__construct($map);
	}

	public function has($id)
	{
		return $this->offsetExists($id);
	}

	public function get($id)
	{
		return $this->offsetGet($id);
	}

	public function offsetExists($id)
	{
		if (\is_integer($id))
			return parent::offsetExists($id);

		if (\is_string($id))
			foreach ($this->getIterator() as $value)
			{
				if ($value instanceof NameProviderInterface)
					$value = $value->getName();
				if (TypeDescription::hasStringRepresentation($value) &&
					(\strcasecmp($value, $id) == 0))
					return true;
			}

		return false;
	}

	public function offsetGet($id)
	{
		if (\is_integer($id))
			return parent::offsetGet($id);

		if (\is_string($id))
			foreach ($this->getIterator() as $value)
			{
				$name = $value;
				if ($value instanceof NameProviderInterface)
					$name = $value->getName();
				if (TypeDescription::hasStringRepresentation($name) &&
					(\strcasecmp($name, $id) == 0))
					return $value;
			}

		throw new KeyNotFoundException($id);
	}

	public function offsetSet($name, $value)
	{
		if (\is_integer($name))
			return parent::offsetSet($name, $value);
		return parent::offsetSet(null, $value);
	}

	public function offsetUnset($id)
	{
		if (\is_integer($id))
			return parent::offsetUnset($id);

		if (\is_string($id))
			foreach ($this->getIterator() as $index => $value)
			{
				if ($value instanceof NameProviderInterface)
					$value = $value->getName();
				if (TypeDescription::hasStringRepresentation($value) &&
					(\strcasecmp($value, $id) == 0))
					return parent::offsetUnset($index);
			}
	}
}
