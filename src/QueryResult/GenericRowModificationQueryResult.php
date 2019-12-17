<?php
// NAmespace
namespace NoreSources\SQL\QueryResult;

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
