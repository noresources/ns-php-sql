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

// Aliases
use NoreSources\TypeConversion;
use NoreSources\TypeDescription;
use NoreSources\SQL\DBMS\PreparedStatement;
use NoreSources\SQL\Statement\ParametrizedStatement;
use NoreSources\SQL\Statement\StatementTokenStreamContext;

/**
 * SQLite3 implementation of NoreSources\SQL\SQLitePreparedStatement
 */
class SQLitePreparedStatement extends PreparedStatement
{

	/**
	 *
	 * @param \SQLite3Stmt $statement
	 * @param StatementTokenStreamContext|string $data
	 * @throws \Exception
	 * @throws \BadMethodCallException
	 */
	public function __construct(\SQLite3Stmt $statement, $data = null)
	{
		parent::__construct($data);

		$this->sql = null;
		$this->sqliteStatement = $statement;

		if (version_compare(PHP_VERSION, '7.4.0') < 0) // stmp->getSQL
		{
			if ($data instanceof StatementTokenStreamContext ||
				TypeDescription::hasStringRepresentation($data))
				$this->sql = TypeConversion::toString($data);
			else
				throw new \Exception(
					'Unable to get SQL string from SQLite statement nor ' .
					StatementTokenStreamContext::class);
		}

		if ($data instanceof ParametrizedStatement)
		{
			$npc = $data->getParameters()->getDistinctParameterCount();
			if ($npc != $statement->paramCount())
			{
				throw new \BadMethodCallException(
					'SQLite statement and ' . ParametrizedStatement::class .
					' parameter mismatch. Got ' . $npc . ' for ' . ParametrizedStatement::class .
					' and ' . $statement->paramCount() . ' for SQLiteStmt');
			}
		}
	}

	public function getStatement()
	{
		if (\is_string($this->sql))
		{
			return $this->sql;
		}

		if (\method_exists($this->sqliteStatement, 'getSQL'))
		{
			return $this->sqliteStatement->getSQL(false);
		}

		return '';
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

	/**
	 *
	 * @var \SQLite3Stmt
	 */
	private $sqliteStatement;

	private $sql;
}