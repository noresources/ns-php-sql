<?php

// NAmespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\SQLite\Connection;

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

	/**
	 * @param array|\ArrayObject $settings Connection settings
	 * @throws ConnectionException
	 * @return \NoreSources\SQL\SQLite\Connection
	 */
	public static function createConnection($settings)
	{
		$connection = null;
		$type = ns\ArrayUtil::keyValue($settings, K::CONNECTION_TYPE_SQLITE, K::CONNECTION_TYPE_VIRTUAL);
		if ($type == K::CONNECTION_TYPE_SQLITE)
		{
			if (class_exists('\SQLIte3'))
			{
				$connection = new SQLite\Connection();
			}
			else
			{
				throw new ConnectionException('SQLite3 extension not loaded');
			}
		}

		if ($connection instanceof Connection)
		{
			$connection->connect($settings);
		}
		else
		{
			throw new ConnectionException('Unable to create a Connection with settings ' . var_export($settings, true));
		}

		return $connection;
	}

	/**
	 * @param Connection $connection
	 * @param Statement $statement
	 * @param StructureElement $reference
	 * @return PreparedStatement
	 */
	public static function prepareStatement(Connection $connection, Statement $statement, StructureElement $reference = null)
	{
		$builder = $connection->getStatementBuilder();
		$resolver = new StructureResolver($reference);
		$context = new StatementContext($builder, $resolver);
		$sql = $statement->buildExpression($context);
		$prepared = $connection->prepare($sql, $context);
		return $prepared;
	}
}