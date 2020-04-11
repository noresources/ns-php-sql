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
 * Primary key table column constraint
 */
class PrimaryKeyTableConstraint extends ColumnTableConstraint
{

	/*
	 * @param array $columns Column names on which the key applies.
	 * @param string $name Constraint name
	 */
	public function __construct($columns = [], $name = null)
	{
		parent::__construct($columns, $name);
	}
}

