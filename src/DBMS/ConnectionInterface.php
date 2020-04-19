<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\SQL\Statement\StatementBuilderAwareInterface;
use NoreSources\SQL\Structure\StructureAwareInterface;
use Psr\Log\LoggerAwareInterface;



/**
 * DMBS connection
 */
interface ConnectionInterface extends StatementBuilderAwareInterface, LoggerAwareInterface,
	StructureAwareInterface
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
	 *
	 * @param array $parameters
	 *        	ConnectionInterface parameters
	 */
	function connect($parameters);

	/**
	 * Disconnect to DBMS
	 */
	function disconnect();

	/**
	 *
	 * @return boolean
	 */
	function isConnected();

	/**
	 *
	 * @return \NoreSources\SQL\Statement\StatementBuilderInterface
	 */
	function getStatementBuilder();

	/**
	 *
	 * @return \NoreSources\SQL\Statement\StatementFactoryInterface
	 */
	function getStatementFactory();

	/**
	 *
	 * @param \NoreSources\SQL\Statement\\StatementData|string $statement
	 * @return PreparedStatement
	 */
	function prepareStatement($statement);

	/**
	 * Execute a SQL statement
	 *
	 * @param PreparedStatement|string $statement
	 * @param array $parameters
	 * @return Recordset|integer|boolean
	 */
	function executeStatement($statement, $parameters = array());

	/**
	 * Get the structure of the connected database
	 *
	 * @return StructureElement
	 */
	function getStructure();
}

