<?php

// NAmespace
namespace NoreSources\SQL\SQLite;

// Aliases
use NoreSources as ns;
use NoreSources\SQL as sql;
use NoreSources\SQL\Constants as K;

class Connection implements sql\Connection
{

	public function __construct()
	{
		$this->builder = new StatementBuilder(new sql\ExpressionEvaluator());
		$this->connection = null;
	}

	public function beginTransation()
	{}

	public function commitTransation()
	{}

	public function rollbackTransaction()
	{}

	public function connect($parameters)
	{
		if ($this->connection instanceof \SQLite3)
			$this->connection->close();

		$this->connection = new \SQLite3(':memory:');
		// $this->connection->open(':memory:');
	}

	public function disconnect()
	{
		if (!($this->connection instanceof \SQLite3))
			throw new sql\ConnectionException('Not connected');
		$this->connection->close();
	}

	public function getStatementBuilder()
	{
		return $this->builder;
	}

	public function executeStatement($statement, sql\ParameterArray $parameters = null)
	{
		if (!($this->connection instanceof \SQLite3))
			throw new sql\ConnectionException('Not connected');

		if (is_string($statement) && ns\ArrayUtil::count($parameters))
		{
			$statement = $this->prepare($statement, null);
		}

		$result = null;

		if ($statement instanceof PreparedStatement)
		{
			$stmt = $statement->getSQLite3Stmt();
			$stmt->reset();
			foreach ($parameters as $key => $value)
			{
				$value = ns\ArrayUtil::keyValue($value, sql\ParameterArray::VALUE, null);
				$type = ns\ArrayUtil::keyValue($value, sql\ParameterArray::TYPE, K::kDataTypeUndefined);
				$stmt->bindValue($key, $value, $type);
			}

			$result = $stmt->execute();
		}
		else
		{
			$result = $this->connection->query($statement);
		}
	}

	/**
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Connection::prepare()
	 */
	public function prepare($statement, sql\StatementContext $context)
	{
		if (!($this->connection instanceof \SQLite3))
			throw new sql\ConnectionException('Not connected');

		$stmt = $this->connection->prepare($statement);
		if (!($stmt instanceof \SQLite3Stmt))
			throw new sql\ConnectionException('Unable to create SQLite statement');
			
		return new PreparedStatement($context, $stmt, $statement);
	}

	public static function getSQLiteDataType($sqlType)
	{
		switch ($sqlType){
			case K::kDataTypeBinary: return \SQLITE3_BLOB;
			case K::kDataTypeDecimal: return \SQLITE3_FLOAT;
			case K::kDataTypeNull: return \SQLITE3_NULL;
			case K::kDataTypeInteger:
			case K::kDataTypeBoolean: 
				return \SQLITE3_INTEGER;
			
		}
		return \SQLITE3_TEXT;
	}
	
	private $builder;
	
	private $connection;
}