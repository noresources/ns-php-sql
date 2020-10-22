<?php
namespace NoreSources\SQL\DBMS;

/**
 * Transaction initialization interface
 */
interface TransactionInterface
{

	/**
	 *
	 * @param string $name
	 *        	Transaction block savepoint name
	 *
	 * @return TransactionBlockInterface
	 */
	function newTransactionBlock($name = null);
}
