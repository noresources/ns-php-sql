<?php

// NAmespace
namespace NoreSources\SQL\Reference;

// Aliases
use NoreSources as ns;
use NoreSources\SQL as sql;
use NoreSources\SQL\Constants as K;

/**
 * SQLite connection
 */
class Connection implements sql\Connection
{
	use sql\ConnectionStructureTrait;

	public function __construct()
	{
		$this->builder = new StatementBuilder(new sql\ExpressionEvaluator());
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

	public function executeStatement($statement, sql\ParameterArray $parameters = null)
	{
		return true;
	}

	/**
	 *
	 * @param sql\StatementContext|string $statement
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