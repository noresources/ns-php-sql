<?php

/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\Container;
use NoreSources\SQL\IndexedAssetMap;
use NoreSources\SQL\KeyedAssetMap;
use NoreSources\SQL\NameProviderInterface;
use NoreSources\SQL\Structure\Traits\StructureElementTrait;

class TableStructure implements StructureElementInterface,
	StructureElementContainerInterface
{
	use StructureElementTrait;

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
	}

	/**
	 *
	 * @return \NoreSources\SQL\KeyedAssetMap
	 */
	public function getColumns()
	{
		if (!isset($this->columns))
			$this->columns = new KeyedAssetMap();
		return $this->columns;
	}

	/**
	 *
	 * @return \NoreSources\SQL\IndexedAssetMap
	 */
	public function getConstraints()
	{
		if (!isset($this->constraints))
			$this->constraints = new IndexedAssetMap();
		return $this->constraints;
	}

	public function getColumnConstraintFlags($column)
	{
		if (!isset($this->constraints))
			return 0;

		if ($column instanceof NameProviderInterface)
			$column = $column->getName();

		$flags = 0;
		foreach ($this->constraints as $constraint)
		{
			if ($constraint instanceof IndexTableConstraintInterface)
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

		return $flags;
	}

	/**
	 *
	 * @var KeyedAssetMap
	 */
	private $columns;

	/**
	 *
	 * @var IndexedAssetMap
	 */
	private $constraints;

	/**
	 *
	 * @param TableConstraintInterface $constraint
	 */
	public function addConstraint(TableConstraintInterface $constraint)
	{
		return $this->getConstraints()->append($constraint);
	}

	/**
	 */
	public function offsetGet($offset)
	{
		return $this->getColumns()->get($offset);
	}

	public function getIterator()
	{
		return $this->getColumns()->getIterator();
	}

	/**
	 */
	public function offsetExists($offset)
	{
		return $this->getColumns()->has($offset);
	}

	/**
	 */
	public function get($id)
	{
		return $this->getColumns()->get($id);
	}

	/**
	 */
	public function offsetUnset($offset)
	{
		return $this->getColumns()->offsetUnset($offset);
	}

	/**
	 */
	public function getChildElements()
	{
		return $this->getColumns();
	}

	public function count()
	{
		return $this->getColumns()->count();
	}

	/**
	 */
	public function findDescendant($tree)
	{
		return $this->getColumns()->get($tree);
	}

	/**
	 */
	public function has($id)
	{
		return $this->getColumns()->has($id);
	}

	/**
	 */
	public function appendElement(StructureElementInterface $element)
	{
		$element->setParentElement($this);
		return $this->getColumns()->offsetSet($element->getName(),
			$element);
	}

	/**
	 */
	public function offsetSet($offset, $value)
	{
		$value->setParentElement($this);
		return $this->getColumns()->offsetSet($offset, $value);
	}
}


