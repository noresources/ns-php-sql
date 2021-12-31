<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\Container\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\Traits\ColumnListTrait;
use NoreSources\SQL\Structure\Traits\StructureElementTrait;
use NoreSources\SQL\Syntax\Statement\Traits\WhereConstraintTrait;

/**
 * UNIQUE key column constraints
 */
class UniqueTableConstraint implements KeyTableConstraintInterface
{

	use StructureElementTrait;
	use ColumnListTrait;
	use WhereConstraintTrait;

	public function getConstraintFlags()
	{
		$flags = K::CONSTRAINT_COLUMN_KEY | K::CONSTRAINT_COLUMN_UNIQUE;

		if (isset($this->whereConstraints) &&
			Container::count($this->whereConstraints))
			$flags |= K::CONSTRAINT_COLUMN_PARTIAL;
		return $flags;
	}

	public function getIndexFlags()
	{
		return K::INDEX_UNIQUE;
	}

	public function getConstraintExpression()
	{
		return $this->whereConstraints;
	}

	/**
	 *
	 * @param array $columns
	 *        	Column names on which the key applies.
	 * @param string $name
	 *        	Constraint name
	 */
	public function __construct($columns = array(), $name = null)
	{
		$this->initializeStructureElement($name);
		$this->columnNameList = $columns;
		$this->constraintName = $name;
	}

	public function __clone()
	{
		$this->cloneStructureElement();
		if (isset($this->whereConstraints))
			$this->whereConstraints = clone $this->whereConstraints;
	}
}

