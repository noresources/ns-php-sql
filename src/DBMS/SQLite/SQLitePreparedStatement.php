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
use NoreSources\SQL\Statement\InputDataTrait;
use NoreSources\SQL\Statement\OutputDataTrait;
use NoreSources\SQL\Statement\ParameterDataProviderInterface;
use NoreSources\SQL\Statement\StatementInputDataInterface;
use NoreSources\SQL\Statement\StatementTokenStreamContext;

/**
 * SQLite3 implementation of NoreSources\SQL\SQLitePreparedStatement
 */
class SQLitePreparedStatement implements PreparedStatementInterface
{

	use InputDataTrait;
	use OutputDataTrait;

	/**
	 *
	 * @param \SQLite3Stmt $statement
	 * @param StatementTokenStreamContext|string $data
	 * @throws \Exception
	 * @throws \BadMethodCallException
	 */
	public function __construct(\SQLite3Stmt $statement, $data = null)
	{
		if ($data instanceof StatementInputDataInterface)
			$this->initializeInputData($data);
		else
			$this->initializeInputData(null);
		$this->initializeOutputData($data);

		$this->sqliteStatement = $statement;

		if ($data instanceof ParameterDataProviderInterface)
		{
			$npc = $data->getParameters()->getDistinctParameterCount();
			if ($npc != $statement->paramCount())
			{
				throw new \BadMethodCallException(
					'SQLite statement and ' .
					ParameterDataProviderInterface::class .
					' parameter mismatch. Got ' . $npc . ' for ' .
					ParameterDataProviderInterface::class . ' and ' .
					$statement->paramCount() . ' for SQLiteStmt');
			}
		}

		if (!\method_exists($this->sqliteStatement, 'getSQL'))
			if (TypeDescription::hasStringRepresentation($data))
				$this->sql = TypeConversion::toString($data);
	}

	public function __toString()
	{
		if (\method_exists($this->sqliteStatement, 'getSQL'))
			return $this->sqliteStatement->getSQL(false);

		return $this->sql;
	}

	public function getParameterCount()
	{
		return $this->sqliteStatement->paramCount();
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