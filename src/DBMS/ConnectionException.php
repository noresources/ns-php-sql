<?php

// NAmespace
namespace NoreSources\SQL\DBMS;

// Aliases
class ConnectionException extends \ErrorException
{

	/**
	 *
	 * @param Connection $connection
	 *        	Connection object which raise the exception
	 * @param string $message
	 *        	Error message
	 * @param integer $code
	 *        	Error code
	 */
	public function __construct(Connection $connection = null, $message, $code = null)
	{
		parent::__construct($message, $code);
		$this->connection = $connection;
	}

	/**
	 *
	 * @return \NoreSources\SQL\Connection
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	/**
	 *
	 * @var Connection
	 */
	private $connection;
}
