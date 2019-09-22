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
	{}

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
	 * @param StatementData|string $statement #return \NoreSources\SQL\Reference\PreparedStatement
	 */
	public function prepareStatement($statement)
	{
		return new PreparedStatement($statement);
	}

	/**
	 * @var StatementBuilder
	 */
	private $builder;
}