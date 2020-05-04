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
class TableStructure implements StructureElementContainerInterface, StructureElementInterface
{

	use StructureElementTrait;
	use StructureElementContainerTrait;

	/**
	 *
	 * @param unknown $name
	 * @param NamespaceStructure $parent
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
	 *        	Constraint to add. If The constraint is the primary key constraint, it will replace
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

	public function removeConstraint($constraint)
	{
		foreach ($this->constraints as $i => $c)
		{
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
