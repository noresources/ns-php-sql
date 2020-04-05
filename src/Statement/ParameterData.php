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

use NoreSources\ArrayRepresentation;
use Psr\Container\ContainerInterface;

class ParameterData implements ContainerInterface, \IteratorAggregate, \Countable,
	ArrayRepresentation
{

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
		return $this->getParameterCount();
	}

	/**
	 *
	 * @param string $key
	 * @param string $dbmsName
	 * @return number Parameter index
	 */
	public function appendParameter($key, $dbmsName)
	{
		$c = $this->getParameterCount();

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
	 * @return number Number of parameters
	 */
	public function getParameterCount()
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
	 *
	 * @var \ArrayObject
	 */
	private $entries;
}
