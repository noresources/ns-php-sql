<?php
namespace NoreSources\SQL\DBMS;

interface ConnectionAwareInterface
{

	/**
	 * Set connection
	 *
	 * @param ConnectionInterface $connection
	 */
	function setConnection(ConnectionInterface $connection);
}
