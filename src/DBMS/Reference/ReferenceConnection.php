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

use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\TransactionStackTrait;
use NoreSources\SQL\Statement\ClassMapStatementFactoryTrait;
use NoreSources\SQL\Statement\StatementFactoryInterface;
use NoreSources\SQL\Structure\StructureAwareTrait;
use Psr\Log\LoggerAwareTrait;

/**
 * SQLite connection
 */
class ReferenceConnection implements ConnectionInterface, StatementFactoryInterface
{
	use StructureAwareTrait;
	use LoggerAwareTrait;
	use TransactionStackTrait;

	use ClassMapStatementFactoryTrait;

	public function __construct()
	{
		$this->builder = new ReferenceStatementBuilder();
		$this->initializeStatementFactory();
		$this->setTransactionBlockFactory(
			function ($depth, $name) {
				return new ReferenceTransactionBlock($this, $name);
			});
	}

	public function connect($parameters)
	{
		if (Container::keyExists($parameters, K::CONNECTION_STRUCTURE))
			$this->setStructure($structure)[K::CONNECTION_STRUCTURE];
	}

	public function isConnected()
	{
		return true;
	}

	public function disconnect()
	{
		$this->endTransactions(false);
	}

	public function getStatementBuilder()
	{
		return $this->builder;
	}

	public function getStatementFactory()
	{
		return $this;
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

	/**
	 *
	 * @var StatementBuilder
	 */
	private $builder;
}