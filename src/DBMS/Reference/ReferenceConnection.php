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

// Aliases
use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Connection;
use NoreSources\SQL\DBMS\ConnectionStructureTrait;
use NoreSources\SQL\Statement\ClassMapStatementFactoryTrait;
use NoreSources\SQL\Statement\StatementFactoryInterface;

/**
 * SQLite connection
 */
class ReferenceConnection implements Connection, StatementFactoryInterface
{
	use ConnectionStructureTrait;

	use ClassMapStatementFactoryTrait;

	public function __construct()
	{
		$this->builder = new ReferenceStatementBuilder();
		$this->initializeStatementFactory();
	}

	public function beginTransation()
	{}

	public function commitTransation()
	{}

	public function rollbackTransaction()
	{}

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
	{}

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
	 * @see \NoreSources\SQL\DBMS\Connection::prepareStatement()
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