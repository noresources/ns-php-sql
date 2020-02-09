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

use Psr\Container\ContainerInterface;

// Aliases

/**
 * Map of statement parameters
 *
 * Each parameter have a position and an name
 */
class ParameterMap extends \ArrayObject implements ContainerInterface
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
	 * @param string $key
	 *        	Parameter identifier
	 *        	Parameter name
	 * @return array<int> List of parameter occurence positions
	 */
	public function getNamedParameterPositions($key)
	{
		$key = strval($key);
		$positions = [];

		if (!$this->offsetExists($key))
			return $positions;

		$dbmsName = $this->offsetGet($key);

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

	/**
	 *
	 * @param string|integer $key
	 *        	Parameter position or key
	 *
	 * {@inheritdoc}
	 * @see \Psr\Container\ContainerInterface::has()
	 *
	 * @return boolean
	 */
	public function has($key)
	{
		return $this->offsetExists($key);
	}

	/**
	 *
	 * @param string|integer $key
	 *        	Parameter key or position
	 * {@inheritdoc}
	 * @see \Psr\Container\ContainerInterface::get()
	 *
	 * @return Parameter DBMS name
	 */
	public function get($key)
	{
		if (!$this->offsetExists($key))
			throw new ParameterNotFoundException($key);
		return $this->offsetGet($key);
	}

	public function offsetSet($index, $newval)
	{
		if (\is_string($index))
		{
			if (!$this->offsetExists($index))
				$this->namedParameterCount++;
		}
		elseif (!\is_integer($index))
			throw new \InvalidArgumentException('Invalid index. int or string expected.');

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

