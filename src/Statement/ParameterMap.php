<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

// Namespace
namespace NoreSources\SQL\Statement;

// Aliases
use NoreSources as ns;

/**
 * Parameter iterator
 *
 * Iterate over a certain kind of key (string or index)
 */
class ParameterIterator implements \Iterator
{

	public function __construct(ParameterMap $map, $type)
	{
		$this->iterator = $map->getIterator();
		$this->keyType = $type;
	}

	public function current()
	{
		return $this->iterator->current();
	}

	public function key()
	{
		return $this->iterator->key();
	}

	public function next()
	{
		do
		{
			$this->iterator->next();
		}
		while ($this->iterator->valid() &&
			ns\TypeDescription::getName($this->iterator->key()) != $this->keyType);
	}

	public function valid()
	{
		return $this->iterator->valid();
	}

	public function rewind()
	{
		$this->iterator->rewind();
	}

	/**
	 *
	 * @var \Iterator
	 */
	private $iterator;

	private $keyType;
}

/**
 * Map of statement parameters
 *
 * Each parameter have a position and an name
 */
class ParameterMap extends \ArrayObject
{

	public function getNamedParameterCount()
	{
		return $this->namedParameterCount;
	}

	/**
	 *
	 * @return ParameterIterator
	 */
	public function getNamedParameterIterator()
	{
		return (new ParameterIterator($this, 'string'));
	}

	/**
	 *
	 * @param string $name
	 *        	Parameter name
	 * @return array<int> List of parameter occurence positions
	 */
	public function getNamedParameterPositions($name)
	{
		$name = strval($name);
		$positions = [];

		if (!$this->offsetExists($name))
			return $positions;

		$dbmsName = $this->offsetGet($name);

		foreach ($this as $k => $v)
		{
			if (\is_integer($k) && ($v == $dbmsName))
				$positions[] = $k;
		}

		return $dbmsName;
	}

	/**
	 *
	 * @property-read integer $namedParameterCount
	 * @param string $member
	 * @throws \InvalidArgumentException
	 * @return Integer
	 */
	public function __get($member)
	{
		if ($member == 'namedParameterCount')
			return $this->namedParameterCount;

		throw new \InvalidArgumentException($member);
	}

	/**
	 * Number of parameter occurences
	 *
	 * @return integer Total number of parameter occurences
	 */
	public function count()
	{
		return (parent::count() - $this->namedParameterCount);
	}

	public function offsetSet($index, $newval)
	{
		if (\is_string($index))
		{
			if (!$this->offsetExists($index))
			{
				$this->namedParameterCount++;
			}
		}
		elseif (!\is_integer($index))
		{
			throw new \InvalidArgumentException('Invalid index. int or string expected.');
		}

		parent::offsetSet($index, $newval);
	}

	public function offsetUnset($index)
	{
		if (\is_string($index))
		{
			$this->namedParameterCount--;
		}

		parent::offsetUnset($index);
	}

	public function exchangeArray($input)
	{
		$this->namedParameterCount++;
		parent::exchangeArray($input);
		foreach ($this as $key => $value)
		{
			if (\is_string($key))
				$this->namedParameterCount++;
		}
	}

	private $namedParameterCount;
}

