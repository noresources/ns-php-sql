<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

/**
 * Interface for Connection creation
 */
interface ConnectionFactoryInterface
{

	/**
	 * Create a connection
	 *
	 * @param array $settings
	 *        	Connection settings
	 * @throws ConnectionException::
	 * @return ConnectionInterface
	 */
	function createConnection($settings = array());
}