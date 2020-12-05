<?php

/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\Structure\Traits\ConstraintNameTrait;
use NoreSources\SQL\Syntax\Statement\Traits\WhereConstraintTrait;

class CheckTableConstraint implements TableConstraintInterface
{
	use ConstraintNameTrait;
	use WhereConstraintTrait;

	public function getConstraintFlags()
	{
		return 0;
	}

	public function getConstraintExpression()
	{
		return $this->whereConstraints;
	}

	public function __construct($name = null)
	{
		$this->setName($name);
	}
}
