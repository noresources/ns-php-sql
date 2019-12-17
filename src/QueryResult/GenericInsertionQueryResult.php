<?php
// NAmespace
namespace NoreSources\SQL\QueryResult;

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