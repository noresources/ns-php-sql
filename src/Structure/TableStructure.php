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

/**
 * Table structure definition
 *
 * @todo table constraints (primary keys etc. & index)
 */
class TableStructure implements StructureElementContainerInterface, StructureElementInterface,
	ColumnDescriptionMapInterface
{

	use StructureElementTrait;
	use StructureElementContainerTrait;

	/**
	 *
	 * @param string $name
	 *        	Table name
	 * @param NamespaceStructure $parent
	 *        	Parent namespace
	 */
	public function __construct($name, StructureElementContainerInterface $parent = null)
	{
		$this->initializeStructureElement($name, $parent);
		$this->initializeStructureElementContainer();
		$this->constraints = new \ArrayObject();
	}

	public function __clone()
	{
		$this->cloneStructureElement();
		$this->cloneStructureElementContainer();
	}

	public function getColumnCount()
	{
		return $this->count();
	}

	public function hasColumn($name)
	{
		foreach ($this->getIterator() as $key => $value)
		{
			if (\strcasecmp($key, $name) == 0)
				return true;
		}

		return false;
	}

	public function getColumn($name)
	{
		if ($this->offsetExists($name))
			return $this->offsetGet($name);

		foreach ($this->getIterator() as $key => $value)
		{
			if (\strcasecmp($key, $name) == 0)
				return $value;
		}

		throw new ColumnNotFoundException($name);
	}

	public function getColumnIterator()
	{
		return $this->getIterator();
	}

	/**
	 *
	 * @return TableConstraint[]
	 */
	public function getConstraints()
	{
		return $this->constraints;
	}

	/**
	 * Add table constraint
	 *
	 * @param TableConstraint $constraint
	 *        	Constraint to add. If The constraint is the primary key constraint, it will
	 *        	replace
	 *        	the existing one.
	 * @throws StructureException
	 */
	public function addConstraint(TableConstraint $constraint)
	{
		if ($constraint instanceof PrimaryKeyTableConstraint)
		{
			foreach ($this->constraints as $value)
			{
				if ($value instanceof PrimaryKeyTableConstraint)
					throw new StructureException('Primary key already exists.', $this);
			}
		}

		$this->constraints->append($constraint);
	}

	/**
	 *
	 * @param TableConstraint|integer $constraint
	 */
	public function removeConstraint($constraint)
	{
		if (\is_integer($constraint))
		{
			$this->constraints->offsetUnset($constraint);
			return;
		}

		foreach ($this->constraints as $i => $c)
		{
			/**
			 *
			 * @var TableConstraint $c
			 */
			if (\is_string($constraint))
			{
				if ($constraint->constraintName == $constraint)
				{
					$this->constraints->offsetUnset($i);
					return;
				}
			}

			if ($c === $constraint)
				$this->constraints->offsetUnset($i);
		}
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $constraints;
}
