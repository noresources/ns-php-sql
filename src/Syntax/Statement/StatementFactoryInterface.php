<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement;

/**
 * Statement & query factory
 */
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