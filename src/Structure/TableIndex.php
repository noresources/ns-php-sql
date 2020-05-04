<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

class TableIndex implements \IteratorAggregate
{

	const UNIQUE = 0x01;

	public function __construct($name = null)
	{
		$this->indexName = $name;
	}

	/**
	 *
	 * @return \Iterator
	 */
	public function getIterator()
	{
		return $this->indexColumns->getIterator();
	}

	/**
	 *
	 * @return number
	 */
	public function getIndexFlags()
	{
		return $this->indexFlags;
	}

	public function setIndexFlags($flags)
	{
		$this->indexFlags = $flags;
	}

	/**
	 *
	 * @return string
	 */
	public function getIndexName()
	{
		return $this->indexName;
	}

	/**
	 *
	 * @return ArrayObject
	 */
	public function getColumns()
	{
		return $this->indexColumns;
	}

	/**
	 *
	 * @param ColumnStructure|Evaluable $column
	 * @return \NoreSources\SQL\Structure\TableIndex
	 */
	public function addColumn($column)
	{
		$this->indexColumns->append($column);
		return $this;
	}

	/**
	 *
	 * @var string
	 */
	private $indexName;

	/**
	 *
	 * @var \ArrayObject
	 */
	private $indexColumns;

	/**
	 *
	 * @var integer
	 */
	private $indexFlags;
}