<?php

// NAmespace
namespace NoreSources\SQL\PDO;

// Aliases
use NoreSources as ns;
use NoreSources\SQL as sql;
use NoreSources\SQL\Constants as K;

class PreparedStatement extends sql\PreparedStatement
{

	/**
	 *
	 * @param \PDOStatement $statement
	 * @param string|sql\StatementData $data
	 */
	public function __construct(\PDOStatement $statement, $data)
	{
		parent::__construct($data);
		$this->statement = $statement;
		$this->statementOwner = null;
	}

	public function __destruct()
	{
		$this->statement->closeCursor();
	}

	public function getStatement()
	{
		return $this->statement->queryString;
	}

	public function acquirePDOStatement($by)
	{
		if ($this->statementOwner !== null)
		{
			if ($this->statementOwner !== $by)
			{
				throw new \LogicException(
					'Statement is already acquired by ' .
					ns\TypeDescription::getName($this->statementOwner));
			}
		}

		$this->statementOwner = $by;
	}

	public function releasePDOStatement($by)
	{
		if ($this->statementOwner === null)
		{
			if ($this->statementOwner !== $by)
			{
				throw new \LogicException(
					ns\TypeDescription::getName($by) . ' is not the owner of the PDOStatement');
			}
		}

		$this->statementOwner = null;
	}

	public function isPDOStatementAcquired()
	{
		return ($this->statementOwner !== null);
	}

	public function getPDOStatement()
	{
		return $this->statement;
	}

	/**
	 *
	 * @var \PDOStatement
	 */
	private $statement;

	private $statementOwner;
}