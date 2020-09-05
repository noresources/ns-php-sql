<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\SQL\DBMS\PreparedStatementInterface;
use NoreSources\SQL\Statement\StatementDataTrait;

/**
 * MySQL3 implementation of NoreSources\SQL\MySQLPreparedStatement
 */
class MySQLPreparedStatement implements PreparedStatementInterface
{
	use StatementDataTrait;

	/**
	 *
	 * @param \mysqli_stmt $statement
	 * @param string|\NoreSources\SQL\Statement\StatementOutputDataInterface $data
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

	public function getParameterCount()
	{
		return $this->mysqlStatement->param_count;
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