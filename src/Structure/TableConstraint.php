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

use NoreSources\SQL\Constants as K;

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

