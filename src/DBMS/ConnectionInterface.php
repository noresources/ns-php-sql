<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\SQL\DBMS\Configuration\ConfiguratorProviderInterface;
use NoreSources\SQL\Result\Recordset;

/**
 * DMBS connection
 */
interface ConnectionInterface extends PlatformProviderInterface,
	ConfiguratorProviderInterface
{

	/**
	 *
	 * @return boolean
	 */
	function isConnected();

	/**
	 *
	 * @param \NoreSources\SQL\Syntax\Statement\\StatementData|string $statement
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

