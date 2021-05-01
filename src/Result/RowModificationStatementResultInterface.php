<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Result;

/**
 * Interface for all results of a query that modify or remove rows of a table.
 */
interface RowModificationStatementResultInterface extends StatementResultInterface
{

	function getAffectedRowCount();
}

