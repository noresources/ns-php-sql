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
use NoreSources\SQL\Structure\StructureProviderTrait;
use Psr\Log\LoggerAwareTrait;
use NoreSources\SQL\Structure\StructureElementInterface;

/**
 * SQLite connection
 */
class ReferenceConnection implements ConnectionInterface, StatementFactoryInterface
{
	use StructureProviderTrait;
	use LoggerAwareTrait;
	use TransactionStackTrait;

	use ClassMapStatementFactoryTrait;

	public function __construct($parameters)
	{
		$this->builder = new ReferenceStatementBuilder();
		$this->initializeStatementFactory();
		$this->setTransactionBlockFactory(
			function ($depth, $name) {

				return new ReferenceTransactionBlock($this, $name);
			});

		$structure = Container::keyValue($parameters, K::CONNECTION_STRUCTURE);
		if ($structure instanceof StructureElementInterface)
			$this->setStructure($structure);
	}

	public function __destruct()
	{
		$this->endTransactions(false);
	}

	public function isConnected()
	{
		return true;
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