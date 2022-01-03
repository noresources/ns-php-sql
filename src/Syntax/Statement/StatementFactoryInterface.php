<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
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
	 * @param string $statementType
	 *        	Statement base class name
	 * @throws StatementNotAvailableException
	 * @return Statement
	 */
	function newStatement($statementType);

	/**
	 *
	 * @param string $statementType
	 *        	Statement base class name
	 * @return boolean
	 */
	function hasStatement($statementType);
}