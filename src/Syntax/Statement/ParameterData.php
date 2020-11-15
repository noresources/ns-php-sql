<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

//
namespace NoreSources\SQL\Syntax\Statement;

use NoreSources\ArrayRepresentation;
use NoreSources\Container;
use Psr\Container\ContainerInterface;

/**
 * Internal class used to bind logical parameter key and DBMS parameter names
 */
class ParameterData implements ContainerInterface, \IteratorAggregate,
	\Countable, ArrayRepresentation
{

	public function __construct()
	{
		$this->entries = new \ArrayObject();
	}

	public function getKeyIterator()
	{
		return new ParameterIterator($this->entries, 'string');
	}

	public function getIterator()
	{
		return new ParameterIterator($this->entries, 'integer');
	}

	/**
	 *
	 * @return number Number of parameters
	 */
	public function count()
	{
		$c = 0;
		foreach ($this->entries as $key => $entry)
		{
			if (\is_integer($key))
				$c++;
		}

		return $c;
	}

	/**
	 *
	 * @param string $key
	 * @param string $dbmsName
	 * @return number Parameter index
	 */
	public function appendParameter($key, $dbmsName)
	{
		$c = $this->count();

		$this->entries[$c] = [
			self::KEY => $key,
			self::DBMSNAME => $dbmsName
		];

		if ($this->entries->offsetExists($key))
		{
			$this->entries[$key][self::POSITIONS][] = $c;
		}
		else
		{
			$this->entries[$key] = [
				self::DBMSNAME => $dbmsName,
				self::POSITIONS => [
					$c
				]
			];
		}

		return $c;
	}

	/**
	 *
	 * @param integer $index
	 *        	Zero-based parameter position
	 * @param string|NULL $key
	 *        	Parameter key
	 * @param string $dbmsName
	 *        	Parameter DBMS name
	 */
	public function setParameter($index, $key, $dbmsName)
	{
		$index = \intval($index);
		if ($key === null)
			$key = \strval($index);

		if ($this->entries->offsetExists($index))
		{
			$previousKey = $this->entries[$index][self::KEY];
			if ($this->entries->offsetExists($previousKey))
				$this->entries[$previousKey][self::POSITIONS] = Container::filter(
					$this->entries[$previousKey][self::POSITIONS],
					function ($k, $v) use ($index) {
						return ($v != $index);
					});
		}

		$this->entries[$index] = [
			self::KEY => $key,
			self::DBMSNAME => $dbmsName
		];

		if ($this->entries->offsetExists($key))
		{
			$this->entries[$key][self::POSITIONS][] = $index;
		}
		else
		{
			$this->entries[$key] = [
				self::DBMSNAME => $dbmsName,
				self::POSITIONS => [
					$index
				]
			];
		}
	}

	/**
	 *
	 * @return number Number of distinct parameter keys
	 */
	public function getDistinctParameterCount()
	{
		$c = 0;
		foreach ($this->entries as $key => $entry)
		{
			if (\is_string($key))
				$c++;
		}

		return $c;
	}

	/**
	 *
	 * @param
	 *        	integer|string Parameter key or string
	 */
	public function has($keyOrIndex)
	{
		return $this->entries->offsetExists($keyOrIndex);
	}

	/**
	 *
	 * @param
	 *        	integer|string Parameter index or key
	 * @return array Parameter informations
	 */
	public function get($keyOrIndex)
	{
		if (!$this->entries->offsetExists($keyOrIndex))
			throw new ParameterNotFoundException($keyOrIndex);

		return $this->entries->offsetGet($keyOrIndex);
	}

	/**
	 * For debug purpose
	 *
	 * @return array
	 */
	public function getArrayCopy()
	{
		return $this->entries->getArrayCopy();
	}

	/**
	 * Clear all parameter data
	 */
	public function clear()
	{
		$this->entries->exchangeArray([]);
	}

	/**
	 * Parameter information key.
	 * Parameter positions
	 *
	 * @var string
	 */
	const POSITIONS = 'positions';

	/**
	 * Parameter information key..
	 * Parameter DBMS name
	 *
	 * @var string
	 */
	const DBMSNAME = 'dbmsname';

	/**
	 * Parameter information key..
	 * Parameter logical name
	 *
	 * @var string
	 */
	const KEY = 'key';

	/**
	 *
	 * @var \ArrayObject
	 */
	private $entries;
}
