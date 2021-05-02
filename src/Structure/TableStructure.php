<?php

/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\KeyedAssetMap;
use NoreSources\SQL\NameProviderInterface;
use NoreSources\SQL\Structure\Traits\StructureElementContainerTrait;
use NoreSources\SQL\Structure\Traits\StructureElementTrait;

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

	public function getColumnConstraintFlags($column)
	{
		if ($column instanceof NameProviderInterface)
			$column = $column->getName();

		$flags = 0;
		foreach ($this->getConstraints() as $constraint)
		{
			if ($constraint instanceof KeyTableConstraintInterface)
			{
				if (Container::valueExists($constraint->getColumns(),
					$column))
					$flags |= $constraint->getConstraintFlags();
			}
			elseif ($constraint instanceof ForeignKeyTableConstraint)
			{
				if (Container::keyExists($constraint->getColumns(),
					$column))
					$flags |= $constraint->getConstraintFlags();
			}
		}

		$indexes = $this->getChildElements(IndexStructure::class);
		foreach ($indexes as $index)
		{
			/**
			 *
			 * @var IndexStructure $index
			 */
			if (Container::valueExists($index->getColumns(), $column))
				$flags |= K::CONSTRAINT_COLUMN_KEY;
		}

		return $flags;
	}

	/**
	 *
	 * @param TableConstraintInterface $constraint
	 */
	public function addConstraint(TableConstraintInterface $constraint)
	{
		return $this->appendElement($constraint);
	}
}


