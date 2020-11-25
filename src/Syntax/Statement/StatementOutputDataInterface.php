<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement;

/**
 * Statement building data
 */
interface StatementOutputDataInterface
{

	/**
	 *
	 * @return integer
	 */
	function getStatementType();

	/**
	 *
	 * @return ResultColumnMap
	 */
	function getResultColumns();
}

