<?php
namespace NoreSources\SQL\DBMS;

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
