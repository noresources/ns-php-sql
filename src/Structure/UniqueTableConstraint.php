<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

/**
 * UNIQUE table constraint
 */
class UniqueTableConstraint extends ColumnTableConstraint
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

