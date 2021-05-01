<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

/**
 * Exception raised on transaction instruction failure or due to logic error
 */
class TransactionBlockException extends \LogicException
{

	/**
	 * This may occur when the user attempt to commit a rolled back block and vice versa.
	 *
	 * @var integer
	 */
	const INVALID_STATE = 1;

	/**
	 * The ttransaction instruction execution has failed.
	 *
	 * @var integer
	 */
	const EXECUTION_ERROR = 2;

	/**
	 *
	 * @param string $message
	 *        	Exception message
	 * @param integer $code
	 *        	Error code
	 */
	public function __construct($message, $code = 0)
	{
		parent::__construct($message, $code);
	}
}