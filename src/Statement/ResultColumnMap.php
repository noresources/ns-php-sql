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
 */
class ResultColumnMap implements \Countable, \IteratorAggregate
{

	public function __construct()
	{
		$this->columns = new \ArrayObject();
	}

	public function __get($key)
	{
		return $this->getColumn($key);
	}

	public function getIterator()
	{
		return $this->columns->getIterator();
	}

	public function count()
	{
		return $this->columns->count();
	}

	/**
	 *
	 * @param integer|string $key
	 * @throws \InvalidArgumentException
	 * @return ResultColumn
	 */
	public function getColumn($key)
	{
		if (!$this->columns->offsetExists($key))
		{
			foreach ($this->columns as $column)
			{
				if ($column->name == $key)
					return $column;
			}

			throw new \InvalidArgumentException(
				ns\TypeDescription::getName($key) . ' ' . $key . ' is not a valid result column key');
		}

		return $this->columns->offsetGet($key);
	}

	public function setColumn($index, $data)
	{
		if (!($data instanceof ResultColumn))
			$data = new ResultColumn($data);
		$this->columns->offsetSet($index, $data);
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $columns;
}


