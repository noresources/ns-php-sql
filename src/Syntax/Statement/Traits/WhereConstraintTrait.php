<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Traits;

trait WhereConstraintTrait
{
	use ConstraintExpressionListTrait;

	/**
	 * WHERE constraints
	 *
	 * @param Evaluable[] $...
	 *        	One or more evaluable element. Elements will be joined with the AND operator
	 */
	public function where()
	{
		if (!($this->whereConstraints instanceof \ArrayObject))
			$this->initializeWhereConstraints();

		return $this->addConstraints($this->whereConstraints,
			func_get_args());
	}

	/**
	 * Initialize WhereConstraintTrait private members.
	 * This should be called in class constructors.
	 */
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
