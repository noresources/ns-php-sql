<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

//
namespace NoreSources\SQL\Statement;

/**
 * Exception raised while building statement SQL string
 */
class StatementException extends \Exception
{
	
	public function __construct(Statement $statement, $message)
	{
		parent::__construct($message);
		$this->statement = $statement;
	}
	
	/**
	 *
	 * @return \NoreSources\SQL\Statement\Statement
	 */
	public function getStatement()
	{
		return $this->statement;
	}
	
	/**
	 *
	 * @var Statement
	 */
	private $statement;
}