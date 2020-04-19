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
namespace NoreSources\SQL\Statement\Traits;

trait WhereConstraintTrait
{
	use ConstraintExpressionListTrait;

	/**
	 * WHERE constraints
	 *
	 * @param
	 *        	Evaluable ...
	 */
	public function where()
	{
		if (!($this->whereConstraints instanceof \ArrayObject))
			$this->initializeWhereConstraints();

		$this->addConstraints($this->whereConstraints, func_get_args());
	}

	protected function initializeWhereConstraints()
	{
		$this->whereConstraints = new \ArrayObject();
	}

	/**
	 * WHERE conditions
	 *
	 * @var \ArrayObject
	 */
	protected $whereConstraints;
}
