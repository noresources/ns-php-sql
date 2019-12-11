<?php

// NAmespace
namespace NoreSources\SQL\SQLite;

// Aliases
use NoreSources as ns;
use NoreSources\SQL as sql;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\StatementData;

/**
 * SQLite3 implementation of NoreSources\SQL\PreparedStatement
 */
class PreparedStatement extends sql\PreparedStatement
{

	/**
	 *
	 * @param \SQLite3Stmt $statement
	 * @param sql\BuildContext|string $data
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
			if ($data instanceof sql\BuildContext || \is_string($data))
			{
				$this->sql = strval($data);
			}
			else
			{
				throw new \Exception(
					'Unable to get SQL string from SQLite statement nor Statement\InputData');
			}
		}

		if ($data instanceof sql\Statement\InputData)
		{
			if ($data->getNamedParameterCount() != $statement->paramCount())
			{
				echo ($data . PHP_EOL);
				throw new \BadMethodCallException(
					'SQLite statement and Statement\InputData parameter mismatch. Got ' .
					$data->getNamedParameterCount() . ' for Statement\InputData and ' .
					$statement->paramCount() . ' for SQLiteStmt');
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