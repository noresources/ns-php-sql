<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\Traits\ColumnListTrait;
use NoreSources\SQL\Structure\Traits\StructureElementTrait;
use NoreSources\SQL\Syntax\Statement\Traits\WhereConstraintTrait;

/**
 * Unique and/or partial index
 */
class IndexTableConstraint implements IndexTableConstraintInterface
{

	use StructureElementTrait;
	use ColumnListTrait;
	use WhereConstraintTrait;

	const UNIQUE = K::INDEX_UNIQUE;

	public function getConstraintFlags()
	{
		$flags = K::CONSTRAINT_COLUMN_KEY;
		if ($this->getIndexFlags() & K::INDEX_UNIQUE)
			$flags |= K::CONSTRAINT_COLUMN_UNIQUE;
		if (isset($this->whereConstraints) &&
			Container::count($this->whereConstraints))
			$flags |= K::CONSTRAINT_COLUMN_PARTIAL;
		return $flags;
	}

	public function getIndexFlags()
	{
		return $this->indexFlags;
	}

	public function getConstraintExpression()
	{
		return $this->whereConstraints;
	}

	public function unique($value)
	{
		$this->indexFlags &= ~K::INDEX_UNIQUE;
		if ($value)
			$this->indexFlags |= K::INDEX_UNIQUE;
		return $this;
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
		$this->indexFlags = 0;
	}

	/**
	 *
	 * @var integer
	 */
	private $indexFlags;
}

