<?php
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
