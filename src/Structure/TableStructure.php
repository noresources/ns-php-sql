<?php

/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\KeyedAssetMap;
use NoreSources\SQL\Structure\Inspector\StructureInspector;
use NoreSources\SQL\Structure\Traits\StructureElementContainerTrait;
use NoreSources\SQL\Structure\Traits\StructureElementTrait;
use NoreSources\Type\TypeDescription;

class TableStructure implements StructureElementInterface,
	StructureElementContainerInterface
{
	use StructureElementTrait;
	use StructureElementContainerTrait;

	/**
	 *
	 * @param string $name
	 *        	Table name
	 * @param StructureElementInterface $parent
	 *        	Container
	 */
	public function __construct($name, $parent = null)
	{
		$this->initializeStructureElement($name, $parent);
		$this->initializeStructureElementContainer();
	}

	public function __clone()
	{
		$this->cloneStructureElement();
		$this->cloneStructureElementContainer();
	}

	/**
	 *
	 * @return \NoreSources\SQL\KeyedAssetMap
	 */
	public function getColumns()
	{
		return new KeyedAssetMap(
			$this->getChildElements(ColumnStructure::class));
	}

	/**
	 *
	 * @return \NoreSources\SQL\IndexedAssetMap
	 */
	public function getConstraints()
	{
		return new KeyedAssetMap(
			$this->getChildElements(TableConstraintInterface::class));
	}

	/**
	 * Get constraint flags for a given column
	 *
	 * @param string|ColumnStructure $column
	 *        	Column or column name
	 * @throws \InvalidArgumentException
	 * @return number Column constraint flags
	 * @deprecated Use StructureInspector directly
	 */
	public function getColumnConstraintFlags($column)
	{
		$inspector = StructureInspector::getInstance();
		if (\is_string($column))
			$column = $this->getColumns()[$column];

		if (!($column instanceof ColumnStructure))
			throw new \InvalidArgumentException(
				ColumnStructure::class . ' expected. Got ' .
				TypeDescription::getName($column));

		return $inspector->getTableColumnConstraintFlags($column);
	}

	/**
	 *
	 * @param TableConstraintInterface $constraint
	 * @deprecated Use appendElement()
	 */
	public function addConstraint(TableConstraintInterface $constraint)
	{
		return $this->appendElement($constraint);
	}
}


