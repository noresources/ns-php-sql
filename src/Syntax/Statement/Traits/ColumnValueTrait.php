<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Traits;

use NoreSources\Expression\ExpressionInterface;
use NoreSources\SQL\Syntax\Evaluator;

/**
 * Store column values in UPDATE and INSERT queries
 *
 * Implements \ArrayAccess
 */
trait ColumnValueTrait
{

	/**
	 * Set a column value with an evaluable value
	 *
	 * @param
	 *        	string Column name
	 * @param
	 *        	Evaluable Evaluable expression
	 *
	 * @throws \BadMethodCallException
	 * @throws \InvalidArgumentException
	 */
	public function __invoke()
	{
		$args = func_get_args();
		if (count($args) != 2)
			throw new \BadMethodCallException(
				__CLASS__ . ' invokation expects exactly 2 arguments');

		if (!\is_string($args[0]))
			throw new \InvalidArgumentException(
				__CLASS__ . '() first argument expects string');

		$this->setColumnData($args[0], $args[1], true);
	}

	/**
	 *
	 * @param string $columnName
	 * @param mixed $columnValue
	 * @param boolean $evaluate
	 *        	If @c true, the value will be evaluated at build stage. Otherwise, the value is
	 *        	considered as a
	 *        	literal of the same type as the column data type..
	 *        	If @c null, the
	 * @return \NoreSources\SQL\Syntax\Statement\Manipulation\UpdateQuery
	 */
	public function setColumnData($columnName, $columnValue,
		$evaluate = null)
	{
		if ($evaluate === null)
			$evaluate = !($columnName instanceof ExpressionInterface);

		if ($evaluate)
			$columnValue = Evaluator::evaluate($columnValue);

		$this->columnValues->offsetSet($columnName, $columnValue);
		return $this;
	}

	/**
	 *
	 * @param
	 *        	string Column name
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return $this->columnValues->offsetExists($offset);
	}

	/**
	 * Get current column value
	 *
	 * @param
	 *        	string Column name
	 *
	 * @return mixed Column current value or @c null if not set
	 */
	public function offsetGet($offset)
	{
		if ($this->columnValues->offsetExists($index))
			return $this->columnValues[$offset]['value'];
		return null;
	}

	/**
	 *
	 * @param string $offset
	 *        	Column name
	 * @param mixed $value
	 *        	Column value.
	 */
	public function offsetSet($offset, $value)
	{
		$evaluate = false;
		$this->setColumnData($offset, $value, $evaluate);
	}

	/**
	 *
	 * @param string $offset
	 *        	Column name
	 */
	public function offsetUnset($offset)
	{
		$this->columnValues->offsetUnset($offset);
	}

	/**
	 *
	 * @var \ArrayObject Associative array where
	 *      keys are column names
	 *      and values are ExpressionInterface
	 */
	private $columnValues;
}
