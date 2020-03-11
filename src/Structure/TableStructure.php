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
 * Table properties
 *
 * @todo table constraints (primary keys etc. & index)
 */
class TableStructure extends StructureElement
{

	/**
	 *
	 * @param TablesetStructure $a_tablesetStructure
	 * @param string $name
	 */
	public function __construct(/*TablesetStructure */ $a_tablesetStructure, $name)
	{
		parent::__construct($name, $a_tablesetStructure);

		$this->constraints = new \ArrayObject();
	}

	/**
	 *
	 * @return ArrayObject
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
				{
					throw new StructureException($this, 'Primary key already exists.');
				}
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
