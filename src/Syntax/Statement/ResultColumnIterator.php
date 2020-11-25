<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement;

class ResultColumnIterator extends \ArrayIterator
{

	public function __construct(ResultColumnMap $map)
	{
		parent::__construct($map->getIterator());
	}

	public function key()
	{
		return $this->current()->name;
	}
}
