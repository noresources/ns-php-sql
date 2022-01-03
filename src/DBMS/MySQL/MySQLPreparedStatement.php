<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\SQL\DBMS\PreparedStatementInterface;
use NoreSources\SQL\Syntax\Statement\ResultColumnProviderInterface;
use NoreSources\SQL\Syntax\Statement\StatementTypeProviderInterface;
use NoreSources\SQL\Syntax\Statement\Traits\StatementDataTrait;
use NoreSources\SQL\Syntax\Statement\Traits\StatementSerializationTrait;

/**
 * MySQL3 implementation of NoreSources\SQL\MySQLPreparedStatement
 */
class MySQLPreparedStatement implements PreparedStatementInterface
{
	use StatementDataTrait;
	use StatementSerializationTrait;

	/**
	 *
	 * @param \mysqli_stmt $statement
	 * @param string|StatementTypeProviderInterface|ResultColumnProviderInterface $data
	 * @throws \Exception
	 */
	public function __construct(\mysqli_stmt $statement, $data = null)
	{
		$this->initializeStatementData($data);
		$this->mysqlStatement = $statement;
	}

	public function __destruct()
	{
		$this->mysqlStatement->close();
	}

	public function getMySQLStmt()
	{
		return $this->mysqlStatement;
	}

	/**
	 *
	 * @var \mysqli_stmt
	 */
	private $mysqlStatement;

	/**
	 *
	 * @var string
	 */
	private $sql;
}