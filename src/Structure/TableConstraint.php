<?php
namespace NoreSources\SQL;

/**
 *
 * @see https://www.sqlite.org/syntax/foreign-key-clause.html
 *
 */
class TableConstraint
{

	/**
	 *
	 * @var string
	 */
	public $constraintName;

	/**
	 *
	 * @param string $name
	 *        	Constraint name
	 */
	protected function __construct($name = null)
	{
		$this->constraintName = $name;
	}
}

