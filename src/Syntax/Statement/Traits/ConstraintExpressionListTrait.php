<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Traits;

use NoreSources\Container;
use NoreSources\Expression\ExpressionInterface;
use NoreSources\SQL\Syntax\BinaryOperation;
use NoreSources\SQL\Syntax\Evaluator;
use NoreSources\SQL\Syntax\Group;

trait ConstraintExpressionListTrait
{

	/**
	 * Store a list of constraints.
	 *
	 * @param \ArrayObject $constraints
	 *        	Array to which constraints definition are added.
	 * @param Evaluable[] $args
	 *        	List of constraints.
	 * @return $this
	 */
	protected function addConstraints(\ArrayObject $constraints, $args)
	{
		if ($constraints->count() && Container::count($args))
		{
			$a = $constraints->getArrayCopy();
			$left = \array_pop($a);
			while (Container::count($a))
			{
				$right = \array_pop($a);
				$left = new BinaryOperation('AND', $left, $right);
			}

			$constraints->exchangeArray([
				new Group($left)
			]);
		}

		foreach ($args as $x)
		{
			if (!($x instanceof ExpressionInterface))
				$x = Evaluator::evaluate($x);

			$constraints->append($x);
		}

		return $this;
	}
}
