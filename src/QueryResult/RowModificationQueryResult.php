<?php
/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\QueryResult;

/**
 * Interface for all results of a query that modify or remove rows of a table.
 */
interface RowModificationQueryResult extends QueryResult
{

	function getAffectedRowCount();
}

