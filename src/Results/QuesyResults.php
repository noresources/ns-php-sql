<?php
// NAmespace
namespace NoreSources\SQL;

// Aliases
interface QueryResult
{
}

interface RowModificationQueryResult extends QueryResult
{

	function getAffectedRowCount();
}

interface InsertionQueryResult extends QueryResult
{

	function getInsertId();
}

class GenericRowModificationQueryResult implements RowModificationQueryResult, \Countable
{

	public function __construct($c)
	{
		$this->affectedRowCount = $c;
	}

	public function getAffectedRowCount()
	{
		return $this->affectedRowCount;
	}

	public function count()
	{
		return $this->affectedRowCount;
	}

	private $affectedRowCount;
}

class GenericInsertionQueryResult implements InsertionQueryResult
{

	public function __construct($insretId)
	{
		$this->insertId;
	}

	public function getInsertId()
	{
		return $this->insertId;
	}

	private $insertId;
}