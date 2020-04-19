<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;


class ConnectionException extends \ErrorException
{

	/**
	 *
	 * @param ConnectionInterface $connection
	 *        	ConnectionInterface object which raise the exception
	 * @param string $message
	 *        	Error message
	 * @param integer $code
	 *        	Error code
	 */
	public function __construct(ConnectionInterface $connection = null, $message, $code = null)
	{
		parent::__construct($message, $code);
		$this->connection = $connection;
	}

	/**
	 *
	 * @return ConnectionInterface
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	/**
	 *
	 * @var ConnectionInterface
	 */
	private $connection;
}
