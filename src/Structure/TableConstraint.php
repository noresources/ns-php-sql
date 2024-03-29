<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\Structure\Traits\StructureElementTrait;

/**
 *
 * @see https://www.sqlite.org/syntax/foreign-key-clause.html
 * @deprecated
 *
 */
abstract class TableConstraint implements TableConstraintInterface
{

	use StructureElementTrait;

	/**
	 *
	 * @param string $name
	 *        	Constraint name
	 */
	protected function __construct($name = null)
	{
		$this->initializeStructureElement($name);
	}

	public function __clone()
	{
		$this->cloneStructureElement();
	}
}

