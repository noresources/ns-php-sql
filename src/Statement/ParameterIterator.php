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

use NoreSources\TypeDescription;

/**
 * Parameter iterator
 *
 * Iterate over a certain kind of key (string or index)
 */
class ParameterIterator implements \Iterator
{

	/**
	 *
	 * @param \IteratorAggregate $map
	 * @param string $type
	 *        	"string" or "integer"
	 */
	public function __construct(\IteratorAggregate $map, $type)
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
			TypeDescription::getName($this->iterator->key()) != $this->keyType);
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

	/**
	 * Iterator key type
	 *
	 * @var string "string" or "integer"
	 */
	private $keyType;
}
