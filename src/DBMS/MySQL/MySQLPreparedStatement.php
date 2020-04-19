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


use NoreSources\TypeConversion;
use NoreSources\TypeDescription;
use NoreSources\SQL\DBMS\PreparedStatement;

/**
 * MySQL3 implementation of NoreSources\SQL\MySQLPreparedStatement
 */
class MySQLPreparedStatement extends PreparedStatement
{

	/**
	 *
	 * @param \mysqli_stmt $statement
	 * @param string|\NoreSources\SQL\Statement\StatementOutputDataInterface $data
	 * @throws \Exception
	 */
	public function __construct(\mysqli_stmt $statement, $data = null)
	{
		parent::__construct($data);
		$this->mysqlStatement = $statement;
		$this->sql = null;

		if (TypeDescription::hasStringRepresentation($data))
		{
			$this->sql = TypeConversion::toString($data);
		}
	}

	public function __destruct()
	{
		$this->mysqlStatement->close();
	}

	public function getStatement()
	{
		return $this->sql;
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