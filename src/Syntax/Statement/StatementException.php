<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement;

/**
 * Exception raised while building statement SQL string
 */
class StatementException extends \Exception
{

	/**
	 *
	 * @param TokenizableStatementInterface $statement
	 * @param unknown $message
	 */
	public function __construct(
		TokenizableStatementInterface $statement, $message)
	{
		parent::__construct($message);
		$this->statement = $statement;
	}

	/**
	 *
	 * @return \NoreSources\SQL\Syntax\Statement\Statement
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