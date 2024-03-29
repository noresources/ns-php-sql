<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\SQL\DBMS\Traits\ConnectionProviderTrait;

/**
 * DBMS connection or command exception
 */
class ConnectionException extends \ErrorException implements
	ConnectionProviderInterface
{

	use ConnectionProviderTrait;

	/**
	 *
	 * @param ConnectionInterface $connection
	 *        	ConnectionInterface object which raise the exception
	 * @param string $message
	 *        	Error message
	 * @param integer $code
	 *        	Error code
	 */
	public function __construct(ConnectionInterface $connection = null,
		$message, $code = null)
	{
		parent::__construct($message, $code);
		$this->setConnection($connection);
	}
}
