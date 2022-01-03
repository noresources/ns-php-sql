<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Result;

/**
 * Interface for all results of a query that add rows in tables.
 */
interface InsertionStatementResultInterface extends
	StatementResultInterface
{

	/**
	 * Get the last sequence / autoincrement generated by the DBMS
	 *
	 * @return mixed
	 */
	function getInsertId();
}

