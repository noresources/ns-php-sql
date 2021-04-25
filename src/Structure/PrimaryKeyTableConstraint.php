<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\Traits\ColumnListTrait;
use NoreSources\SQL\Structure\Traits\StructureElementTrait;

/**
 * Primary key table column constraint
 */
class PrimaryKeyTableConstraint implements
	IndexTableConstraintInterface
{

	use StructureElementTrait;
	use ColumnListTrait;

	public function getConstraintFlags()
	{
		return K::CONSTRAINT_COLUMN_PRIMARY_KEY;
	}

	public function getConstraintExpression()
	{
		return null;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Structure\IndexDescriptionInterface::getIndexFlags()
	 */
	public function getIndexFlags()
	{
		return K::INDEX_UNIQUE;
	}

	public function __construct($columns = [], $name = null)
	{
		$this->initializeStructureElement($name);
		$this->columnNameList = $columns;
	}
}

