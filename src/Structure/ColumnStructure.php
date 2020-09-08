<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\Constants as K;

/**
 * Table column properties
 */
class ColumnStructure implements StructureElementInterface,
	ColumnDescriptionInterface
{

	use StructureElementTrait;
	use ColumnDescriptionTrait;

	const DATA_TYPE = K::COLUMN_DATA_TYPE;

	const FLAGS = K::COLUMN_FLAGS;

	const LENGTH = K::COLUMN_LENGTH;

	const FRACTION_DIGIT_COUNT = K::COLUMN_FRACTION_SCALE;

	const ENUMERATION = K::COLUMN_ENUMERATION;

	const DEFAULT_VALUE = K::COLUMN_DEFAULT_VALUE;

	/**
	 *
	 * @return boolean true if Column is part of primary key constraint
	 */
	public function isPrimaryKey()
	{
		/**
		 *
		 * @var TableStructure
		 */
		$table = $this->getParentElement();
		if (!($table instanceof TableStructure))
			return false;

		foreach ($table->getConstraints() as $constraint)
		{
			if ($constraint instanceof PrimaryKeyTableConstraint &&
				($constraint->getColumns()->offsetExists(
					$this->getName())))
				return true;
		}

		return false;
	}

	/**
	 *
	 * @param string $name
	 *        	Column name
	 * @param unknown $tableStructure
	 *        	Parent table
	 */
	public function __construct($name, /*TableStructure */$tableStructure = null)
	{
		$this->initializeStructureElement($name, $tableStructure);
		$this->initializeColumnProperties();
	}

	/**
	 * Clone default value if any.
	 */
	public function __clone()
	{
		$this->cloneStructureElement();
		if ($this->hasColumnProperty(self::DEFAULT_VALUE))
		{
			$this->setColumnProperty(self::DEFAULT_VALUE,
				clone $this->getColumnProperty(self::DEFAULT_VALUE));
		}
	}
}
