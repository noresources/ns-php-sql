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

use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Statement\StatementBuilderProviderInterface;
use NoreSources\SQL\Structure\StructureProviderInterface;

/**
 * DMBS connection
 */
interface ConnectionInterface extends StatementBuilderProviderInterface,
	StructureProviderInterface
{

	/**
	 *
	 * @return boolean
	 */
	function isConnected();

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
}

