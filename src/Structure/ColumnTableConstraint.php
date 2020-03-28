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

use NoreSources\SQL\Constants as K;
use Psr\Container\ContainerInterface;

class ColumnTableConstraint extends TableConstraint implements \IteratorAggregate, \Countable,
	ContainerInterface
{

	/**
	 *
	 * @param array $columns
	 *        	Column names on which the key applies.
	 * @param string $name
	 *        	Constraint name
	 */
	protected function __construct($columns = [], $name = null)
	{
		parent::__construct($name);
		$this->columns = new \ArrayObject($columns);
	}

	/**
	 *
	 * @return ColumnStructure[]
	 */
	public function getColumns()
	{
		return $this->columns;
	}

	/**
	 *
	 * @return integer Number of columns
	 */
	public function count()
	{
		return $this->columns->count();
	}

	/**
	 * Get an interator on columns
	 */
	public function getIterator()
	{
		return $this->columns->getIterator();
	}

	/**
	 *
	 * @param ColumnStructure $column
	 */
	public function append(ColumnStructure $column)
	{
		$this->columns->offsetSet($column->getName(), $column);
		$this->postprocessColumnModification();
	}

	/**
	 *
	 * @param ColumnStructure|string $column
	 * @return boolean
	 */
	public function has($column)
	{
		return $this->columns->offsetExists(
			($column instanceof ColumnStructure) ? $column->getName() : $column);
	}

	/**
	 *
	 * @param ColumnStructure|string $column
	 * @throws \InvalidArgumentException
	 * @return ColumnStructure
	 */
	public function get($column)
	{
		if (!$this->has($column))
			throw new \InvalidArgumentException('Column not found');

		return $this->columns->offsetGet(
			($column instanceof ColumnStructure) ? $column->getName() : $column);
	}

	protected function postprocessColumnModification()
	{
		$this->columns->uasort(
			function ($a, $b) {
				/**
				 *
				 * @var ColumnStructure $a
				 * @var ColumnStructure $b
				 */

				$autoA = ($a->getColumnProperty(K::COLUMN_FLAGS) &
				K::COLUMN_FLAG_AUTO_INCREMENT) ? true : false;

				$autoB = ($b->getColumnProperty(K::COLUMN_FLAGS) &
				K::COLUMN_FLAG_AUTO_INCREMENT) ? true : false;

				if ($autoA)
					return ($autoB ? 0 : -1);
				return ($autoB ? 1 : 0);
			});
	}

	/**
	 * Column names on which the key applies.
	 *
	 * @var \ArrayObject
	 */
	private $columns;
}

