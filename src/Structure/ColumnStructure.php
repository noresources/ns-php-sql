<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\Traits\ColumnDescriptionTrait;
use NoreSources\SQL\Structure\Traits\StructureElementTrait;

/**
 * Table column properties
 */
class ColumnStructure implements StructureElementInterface,
	ColumnDescriptionInterface
{

	use StructureElementTrait;
	use ColumnDescriptionTrait;

	public function getConstraintFlags()
	{
		/**
		 *
		 * @var TableStructure
		 */
		$table = $this->getParentElement();
		$flags = 0;
		if (!($table instanceof TableStructure))
			return $flags;

		foreach ($table->getConstraints() as $constraint)
		{
			if ($constraint instanceof PrimaryKeyTableConstraint &&
				($constraint->getColumns()->offsetExists(
					$this->getName())))
				$flags |= K::COLUMN_CONSTRAINT_PRIMARY_KEY;

			elseif ($constraint instanceof UniqueTableConstraint &&
				($constraint->getColumns()->offsetExists(
					$this->getName())))
				$flags |= K::COLUMN_CONSTRAINT_UNIQUE;
		}

		return $flags;
	}

	/**
	 *
	 * @param string $name
	 *        	Column name
	 * @param TableStructure $tableStructure
	 *        	Parent table
	 */
	public function __construct($name, /*TableStructure */$tableStructure = null)
	{
		$this->initializeStructureElement($name, $tableStructure);
		$this->initializeColumnProperties([
			K::COLUMN_NAME => $name
		]);
	}

	public function setColumnProperty($key, $value)
	{
		$value = ColumnPropertyHelper::normalizeValue($key, $value);
		if ($key == K::COLUMN_NAME &&
			(\strcmp($this->getName(), $value) != 0))
			throw new \LogicException(
				$key . ' column property is immutable in ' .
				static::cloneStructureElement());

		$this->columnProperties[$key] = $value;
		;
	}

	/**
	 * Clone default value if any.
	 */
	public function __clone()
	{
		$this->cloneStructureElement();
		if ($this->has(K::COLUMN_DEFAULT_VALUE))
		{
			$this->setColumnProperty(K::COLUMN_DEFAULT_VALUE,
				clone $this->get(K::COLUMN_DEFAULT_VALUE));
		}
	}
}
