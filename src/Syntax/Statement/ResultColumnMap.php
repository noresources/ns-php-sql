<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\ArrayColumnDescription;
use NoreSources\SQL\Structure\ColumnDescriptionMapInterface;
use NoreSources\SQL\Structure\ColumnNotFoundException;

/**
 * Result columns of a SELECT statement or a Recordset
 */
class ResultColumnMap implements \Countable,
	ColumnDescriptionMapInterface, \IteratorAggregate
{

	public function __construct()
	{
		$this->columns = new \ArrayObject();
	}

	public function __get($key)
	{
		return $this->getColumn($key);
	}

	/**
	 *
	 * @return \Iterator of ResultColumn where Iterator key is the column index
	 */
	public function getIterator()
	{
		return $this->columns->getIterator();
	}

	/**
	 *
	 * @return number of ResultColumn
	 */
	public function count()
	{
		return $this->columns->count();
	}

	public function getColumnCount()
	{
		return $this->count();
	}

	public function hasColumn($name)
	{
		if (\is_integer($name))
			return $this->columns->offsetExists($name);

		foreach ($this->columns as $column)
		{
			if (\strcasecmp($column->getName(), $name) == 0)
				return true;
		}

		return true;
	}

	public function getColumnIterator()
	{
		return new ResultColumnIterator($this);
	}

	/**
	 *
	 * @param integer|string $key
	 *        	Column name or index
	 * @throws ColumnNotFoundException
	 * @return ResultColumn
	 */
	public function getColumn($key)
	{
		if (\is_integer($key))
		{
			if (!$this->columns->offsetExists($key))
				throw new ColumnNotFoundException($key);

			return $this->columns->offsetGet($key);
		}

		foreach ($this->columns as $index => $column)
		{
			if (\strcasecmp($column->getName(), $key) == 0)
				return $column;
		}

		throw new ColumnNotFoundException($key);
	}

	/**
	 *
	 * @param integer $index
	 * @param array $data
	 *        	Column property
	 * @param string $as
	 *        	Optional alias
	 */
	public function setColumn($index, $data, $as = null)
	{
		$data = new ArrayColumnDescription(
			Container::createArray($data));

		if (\is_string($as) && \strlen($as))
			$data->setColumnProperty(K::COLUMN_NAME, $as);

		$this->columns->offsetSet($index, $data);
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $columns;
}

