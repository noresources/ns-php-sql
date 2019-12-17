<?php

// NAmespace
namespace NoreSources\SQL\DBMS\Reference;

// Aliases
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS;
use NoreSources as ns;

/**
 * SQLite connection
 */
class Connection implements dbms\Connection
{
	use dbms\ConnectionStructureTrait;

	public function __construct()
	{
		$this->builder = new StatementBuilder();
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

	public function executeStatement($statement, dbms\StatementParameterArray $parameters = null)
	{
		return true;
	}

	/**
	 *
	 * @param dbms\BuildContext|string $statement
	 *        	#return \NoreSources\SQL\Reference\PreparedStatement
	 */
	public function prepareStatement($statement)
	{
		return new PreparedStatement($statement);
	}

	/**
	 *
	 * @var StatementBuilder
	 */
	private $builder;
}