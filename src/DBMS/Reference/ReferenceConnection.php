<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Reference;

use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\PlatformProviderTrait;
use NoreSources\SQL\DBMS\TransactionInterface;
use NoreSources\SQL\DBMS\TransactionStackTrait;
use NoreSources\SQL\Statement\ClassMapStatementFactoryTrait;

/**
 * SQLite connection
 */
class ReferenceConnection implements ConnectionInterface,
	TransactionInterface
{
	use TransactionStackTrait;
	use PlatformProviderTrait;

	use ClassMapStatementFactoryTrait;

	public function __construct($parameters = array())
	{
		$this->setTransactionBlockFactory(
			function ($depth, $name) {

				return new ReferenceTransactionBlock($this, $name);
			});
	}

	public function __destruct()
	{
		$this->endTransactions(false);
	}

	public function isConnected()
	{
		return true;
	}

	public function getPlatform()
	{
		if (!isset($this->platform))
			$this->platform = new ReferencePlatform();
		return $this->platform;
	}

	public function executeStatement($statement, $parameters = array())
	{
		return true;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\DBMS\ConnectionInterface::prepareStatement()
	 */
	public function prepareStatement($statement)
	{
		return new ReferencePreparedStatement($statement);
	}
}