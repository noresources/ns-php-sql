<?php

// NAmespace
namespace NoreSources\SQL\DBMS\Reference;

// Aliases
use NoreSources\SQL\DBMS;

/**
 * SQLite3 implementation of NoreSources\SQL\PreparedStatement
 */
class PreparedStatement extends dbms\PreparedStatement
{

	/**
	 *
	 * @param StatementData|string $data
	 */
	public function __construct($data)
	{
		parent::__construct($data);
		$this->sql = strval($data);
	}

	public function getStatement()
	{
		return $this->sql;
	}

	/**
	 *
	 * @var string
	 */
	private $sql;
}