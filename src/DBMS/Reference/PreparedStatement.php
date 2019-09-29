<?php

// NAmespace
namespace NoreSources\SQL\Reference;

// Aliases
use NoreSources as ns;
use NoreSources\SQL as sql;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\StatementData;

/**
 * SQLite3 implementation of NoreSources\SQL\PreparedStatement
 */
class PreparedStatement extends sql\PreparedStatement
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