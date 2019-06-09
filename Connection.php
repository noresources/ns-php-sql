<?php

// NAmespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\SQL\Constants as K;

class ConnectionException extends \Exception
{

	public function __construct($message)
	{
		parent::__construct($message);
	}
}

/**
 * DMBS connection
 */
interface Connection
{

	/**
	 * Begin SQL transaction
	 */
	function beginTransation();

	/**
	 * Commit SQL transation
	 */
	function commitTransation();

	/**
	 * Rollback SQL transaction
	 */
	function rollbackTransaction();

	/**
	 * Connect to DBMS
	 * @param unknown $parameters Connection parameters
	 */
	function connect($parameters);

	/**
	 * Disconnect to DBMS
	 */
	function disconnect();

	/**
	 * @return StatementBuilder
	 */
	function getStatementBuilder();

	/**
	 * @param string $statement
	 * @param StatementContext $context Context used to build SQL statement
	 */
	function prepare($statement, StatementContext $context);
	
	/**
	 * @param PreparedStatement|string $statement
	 * @param ParameterArray $parameters
	 * @return Recordset|integer|boolean
	 */
	function executeStatement($statement, ParameterArray $parameters = null);
}

class ConnectionHelper
{
	public static function constructConnection($settings)
	{
		
	}

	/**
	 * 
	 * @param Connection $connection
	 * @param Statement $statement
	 * @param StructureElement $reference
	 * @return PreparedStatement
	 */
	public static function prepareStatement (Connection $connection, Statement $statement, StructureElement $reference = null)
	{
		$builder = $connection->getStatementBuilder();
		$resolver = new StructureResolver($reference);
		$context = new StatementContext($builder, $resolver);
		$sql = $statement->buildExpression($context);
		$prepared = $connection->prepare($sql, $context);
		return $prepared;
	}
}