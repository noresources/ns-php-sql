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

// Aliases

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
	 *
	 * @param \ArrayAccess $parameters
	 *        	Connection parameters
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
	 * @return StatementBuilder
	 */
	function getStatementBuilder();

	/**
	 *
	 * @param BuildContext|string $statement
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

