<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

// Namespace
namespace NoreSources\SQL\Statement;

interface StatementFactoryInterface
{

	/**
	 *
	 * @param integer $statementType
	 *        	Statement type
	 * @return Statement
	 */
	function newStatement($statementType);
}