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
 *
 * @see https://www.sqlite.org/syntax/foreign-key-clause.html
 *
 */
class TableConstraint
{

	public function setName($name)
	{
		if (\is_string($name))
			$this->constraintName = $name;
		else
			$this->constraintName = null;
	}

	public function getName()
	{
		return $this->constraintName;
	}

	/**
	 *
	 * @param string $name
	 *        	Constraint name
	 */
	protected function __construct($name = null)
	{
		$this->constraintName = $name;
	}

	/**
	 *
	 * @var string
	 */
	private $constraintName;
}

