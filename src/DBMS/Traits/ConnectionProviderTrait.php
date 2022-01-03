<?php
/**
 * Copyright © 2012 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Traits;

use NoreSources\SQL\DBMS\ConnectionInterface;

trait ConnectionProviderTrait
{

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
	 * @param ConnectionInterface $connection
	 */
	public function setConnection(ConnectionInterface $connection)
	{
		$this->connection = $connection;
	}

	/**
	 *
	 * @var ConnectionInterface
	 */
	private $connection;
}

