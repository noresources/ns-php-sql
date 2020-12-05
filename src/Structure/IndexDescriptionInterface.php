<?php

/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

interface IndexDescriptionInterface
{

	/**
	 *
	 * @return integer
	 */
	function getIndexFlags();

	/**
	 *
	 * @return Evaluable[]|NULL
	 */
	function getConstraintExpression();

	/**
	 *
	 * @return string[]
	 */
	function getColumns();
}
