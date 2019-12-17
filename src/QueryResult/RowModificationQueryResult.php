<?php
// NAmespace
namespace NoreSources\SQL\QueryResult;

/**
 * Interface for all results of a query that modify or remove rows of a table.
 */
interface RowModificationQueryResult extends QueryResult
{

	function getAffectedRowCount();
}

