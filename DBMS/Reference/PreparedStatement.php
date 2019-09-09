<?php

// NAmespace
namespace NoreSources\SQL\Reference;

// Aliases
use NoreSources as ns;
use NoreSources\SQL as sql;
use NoreSources\SQL\Constants as K;

/**
 * SQLite3 implementation of NoreSources\SQL\PreparedStatement
 */
class PreparedStatement extends sql\PreparedStatement
{

	public function __construct(sql\StatementContext $context, $sql)
	{
		parent::__construct($context);
		$this->sql = $sql;
	}

	public function getStatement()
	{
		return $this->sql;
	}

	public function getParameterCount()
	{
		return $this->sqliteStatement->paramCount();
	}


	/**
	 * @var string
	 */
	private $sql;
}