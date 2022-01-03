<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
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
