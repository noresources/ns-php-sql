<?php

// NAmespace
namespace NoreSources\SQL\SQLite;

// Aliases
use NoreSources as ns;
use NoreSources\SQL as sql;
use NoreSources\SQL\Constants as K;

/**
 * SQLite3 implementation of NoreSources\SQL\PreparedStatement
 */
class PreparedStatement extends sql\PreparedStatement
{

	public function __construct(sql\StatementContext $context, \SQLite3Stmt $statement, $sql = null)
	{
		parent::__construct($context);
		$this->sqliteStatement = $statement;
		$this->sql = $sql;
	}

	public function getStatement()
	{
		if (is_string($this->sql))
			return $this->sql;
		return $this->sqliteStatement->getSQL();
	}

	public function getParameterCount()
	{
		return $this->sqliteStatement->paramCount();
	}

	/**
	 * @return SQLite3Stmt
	 */
	public function getSQLite3Stmt()
	{
		return $this->sqliteStatement;
	}

	/**
	 * @var \SQLite3Stmt
	 */
	private $sqliteStatement;

	private $sql;
}