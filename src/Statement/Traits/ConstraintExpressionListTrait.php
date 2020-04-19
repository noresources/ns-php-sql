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

use NoreSources\SQL\Expression\Evaluator;
use NoreSources\SQL\Expression\TokenizableExpressionInterface;

trait ConstraintExpressionListTrait
{

	protected function addConstraints(\ArrayObject $constraints, $args)
	{
		foreach ($args as $x)
		{
			if (!($x instanceof TokenizableExpressionInterface))
				$x = Evaluator::evaluate($x);

			$constraints->append($x);
		}

		return $this;
	}
}
