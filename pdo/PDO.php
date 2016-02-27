<?php

/**
 * Copyright © 2012-2015 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;
use NoreSources as ns;

use \SQLite3;
use Exception;

class PDOBackend
{
	public function __costruct($dsn)
	{
		
	}
	
	public function startTransaction()
	{
		
	}
	
	public function commitTransaction()
	{
		
	}
	
	function rollbackTransaction()
	{
		
	}
	
	public function executeQuery($a_strQuery)
	{
		
	}
	
	public function lastInsertId()
	{
		
	}
	
	public function fetchResult(QueryResult $a_queryResult, $fetchFlags = kRecordsetFetchBoth)
	{
		
	}
	
	public function resetResult(QueryResult $a_queryResult)
	{
		
	}
	
	public function freeResult(QueryResult $a_queryResult)
	{
		
	}
	
	public function resultRowCount(QueryResult $a_queryResult)
	{
		
	}
	
	public function recordsetColumnArray(QueryResult $a_queryResult)
	{
		
	}
	
	public function affectedRowCount(QueryResult $a_queryResult)
	{
		
	}
	
	private $m_pdoConnection;
}

?>
