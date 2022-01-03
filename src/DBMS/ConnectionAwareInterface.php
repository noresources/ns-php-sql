<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

/**
 * For classes holding a ConnectionInterface reference
 */
interface ConnectionAwareInterface
{

	/**
	 * Set connection
	 *
	 * @param ConnectionInterface $connection
	 */
	function setConnection(ConnectionInterface $connection);
}
