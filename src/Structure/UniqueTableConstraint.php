<?php
namespace NoreSources\SQL;

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

