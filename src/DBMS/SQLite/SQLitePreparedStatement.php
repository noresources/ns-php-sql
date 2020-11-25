<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\TypeConversion;
use NoreSources\TypeDescription;
use NoreSources\SQL\DBMS\PreparedStatementInterface;
use NoreSources\SQL\Syntax\Statement\StatementTokenStreamContext;
use NoreSources\SQL\Syntax\Statement\Traits\StatementInputDataTrait;
use NoreSources\SQL\Syntax\Statement\Traits\StatementOutputDataTrait;
use NoreSources\SQL\Syntax\Statement\Traits\StatementSerializationTrait;

/**
 * SQLite3 implementation of NoreSources\SQL\SQLitePreparedStatement
 */
class SQLitePreparedStatement implements PreparedStatementInterface
{

	use StatementInputDataTrait;
	use StatementOutputDataTrait;
	use StatementSerializationTrait;

	/**
	 *
	 * @param \SQLite3Stmt $statement
	 * @param StatementTokenStreamContext|string $data
	 * @throws \Exception
	 * @throws \BadMethodCallException
	 */
	public function __construct(\SQLite3Stmt $statement, $data = null)
	{
		$this->initializeParameterData($data);
		$this->initializeOutputData($data);
		$this->sqliteStatement = $statement;

		if (!\method_exists($this->sqliteStatement, 'getSQL'))
			if (TypeDescription::hasStringRepresentation($data))
				$this->sql = TypeConversion::toString($data);
	}

	public function __toString()
	{
		if (isset($this->sql))
			return $this->sql;

		return $this->sqliteStatement->getSQL(false);
	}

	/**
	 *
	 * @return \SQLite3Stmt
	 */
	public function getSQLite3Stmt()
	{
		return $this->sqliteStatement;
	}

	public function setSQL($sql)
	{
		$this->sql = $sql;
	}

	/**
	 *
	 * @var \SQLite3Stmt
	 */
	private $sqliteStatement;

	/**
	 *
	 * @var string
	 */
	private $sql;
}