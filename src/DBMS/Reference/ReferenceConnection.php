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
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Connection;
use NoreSources\SQL\DBMS\ConnectionStructureTrait;
use NoreSources\SQL\DBMS\StatementParameterArray;
use NoreSources as ns;

/**
 * SQLite connection
 */
class ReferenceConnection implements Connection
{
	use ConnectionStructureTrait;

	public function __construct()
	{
		$this->builder = new ReferenceStatementBuilder();
	}

	public function beginTransation()
	{}

	public function commitTransation()
	{}

	public function rollbackTransaction()
	{}

	public function connect($parameters)
	{
		if (ns\Container::keyExists($parameters, K::CONNECTION_PARAMETER_STRUCTURE))
			$this->setStructure($structure)[K::CONNECTION_PARAMETER_STRUCTURE];
	}

	public function disconnect()
	{}

	public function getStatementBuilder()
	{
		return $this->builder;
	}

	public function executeStatement($statement, StatementParameterArray $parameters = null)
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