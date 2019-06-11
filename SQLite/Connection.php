<?php

// NAmespace
namespace NoreSources\SQL\SQLite;

// Aliases
use NoreSources as ns;
use NoreSources\SQL as sql;
use NoreSources\SQL\Constants as K;

class Connection implements sql\Connection
{
	const SOURCE_MEMORY = ':memory:';
	const TABLESET_NAME_DEFAULT = 'main';

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

	/**
	 * Connect to DBMS
	 * @param \ArrayAccess $parameters Connection parameters. Accepted keys are
	 *        <ul>
	 *        <li>CONNECTION_PARAMETER_SOURCE (string|array):
	 *        <ul>
	 *        <li>If unspecified, use a in-memory storage</li>
	 *        <li>If the parameter value is a string, the database will be loaded as the "main" database</li>
	 *        <li>If the parameter value is an array, the elements key represents the tableset name,
	 *        the values represents the database
	 *        file name. If the key is not a string, the base file name is used as tableet name</li>
	 *        </ul>
	 *        <li>CONNECTION_PARAMETER_DATABASE (string): Overrides the tableset name if CONNECTION_PARAMETER_SOURCE value is a
	 *        string</li>
	 *        <li>CONNECTION_PARAMETER_CREATE (bool): Create database file if it does not exists</li>
	 *        <li>CONNECTION_PARAMETER_READONLY (bool): Indicates the database is read only</li>
	 *        <li>CONNECTION_PARAMETER_ENCRYPTION_KEY (string): Database encryption key</li>
	 *        </ul>
	 */
	public function connect($parameters)
	{
		if ($this->connection instanceof \SQLite3)
			$this->connection->close();

		$this->connection = null;

		$defaultTablesetName = ns\ArrayUtil::keyValue($parameters, K::CONNECTION_PARAMETER_DATABASE, self::TABLESET_NAME_DEFAULT);

		$sources = ns\ArrayUtil::keyValue($parameters, K::CONNECTION_PARAMETER_SOURCE, array (
				$defaultTablesetName => self::SOURCE_MEMORY
		));

		$flags = 0;
		if (ns\ArrayUtil::keyValue($parameters, K::CONNECTION_PARAMETER_READONLY, false))
		{
			$flags |= \SQLITE3_OPEN_READONLY;
		}
		else
		{
			$flags |= \SQLITE3_OPEN_READWRITE;
		}

		if (ns\ArrayUtil::keyValue($parameters, K::CONNECTION_PARAMETER_CREATE, false))
		{
			if ($flags & \SQLITE3_OPEN_READONLY)
			{
				throw new sql\ConnectionException('Unable to set Auto-create and Read only flags at the same time');
			}

			$flags |= \SQLITE3_OPEN_CREATE;
		}

		if (is_string($sources))
		{
			$sources = array (
					$defaultTablesetName => $source
			);
		}

		$names = array ();
		foreach ($sources as $name => $source)
		{
			$name = self::getTablesetName($name, $source);

			if (\in_array($name, $names))
			{
				throw new ConnectionException('Duplicated tableset name ' . $name);
			}

			$names[] = $name;

			$attach = false;
			$sql = 'ATTACH DATABASE \'' . $this->builder->escapeString($source) . '\' AS ' . $this->builder->escapeIdentifier($name);

			if ($this->connection instanceof \SQLite3)
			{
				$attach = true;
			}
			else
			{
				$key = ns\ArrayUtil::keyValue($parameters, K::CONNECTION_PARAMETER_ENCRYPTION_KEY, null);
				if ($name == self::TABLESET_NAME_DEFAULT)
				{
					$this->connection = new \SQLite3($source, $flags, $key);
				}
				else
				{
					$this->connection = new \SQLite3('', $flags, $key);
					$attach = true;
				}
			}

			if ($attach)
			{
				$this->connection->exec($sql);
			}
		}

		$this->connection = new \SQLite3(self::SOURCE_MEMORY);
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
			$stmt->clear();
			$stmt->reset();
			if ($parameters instanceof sql\ParameterArray)
			{
				foreach ($parameters as $key => $entry)
				{
					$key = $this->builder->getParameter($key);
					$value = ns\ArrayUtil::keyValue($entry, sql\ParameterArray::VALUE, null);
					$type = ns\ArrayUtil::keyValue($entry, sql\ParameterArray::TYPE, K::kDataTypeUndefined);
					
					$type = self::getSQLiteDataType($type);
					$bindResult = $stmt->bindValue($key, $value, $type);
					if (!$bindResult)
						throw new sql\ConnectionException('Failed to bind ' . $key);
				}
			}

			$result = $stmt->execute();
		}
		else
		{
			$result = $this->connection->query($statement);
		}

		if ($result instanceof \SQLite3Result)
		{
			return new Recordset($result);
		}

		throw new sql\ConnectionException('Failed to execute');
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
		switch ($sqlType)
		{
			case K::kDataTypeBinary:
				return \SQLITE3_BLOB;
			case K::kDataTypeDecimal:
				return \SQLITE3_FLOAT;
			case K::kDataTypeNull:
				return \SQLITE3_NULL;
			case K::kDataTypeInteger:
			case K::kDataTypeBoolean:
				return \SQLITE3_INTEGER;
		}
		return \SQLITE3_TEXT;
	}

	private static function getTablesetName($name, $source)
	{
		if (is_string($name))
			return $name;

		if ($source == self::SOURCE_MEMORY)
		{
			return self::TABLESET_NAME_DEFAULT;
		}

		return pathinfo($source, 'filename');
	}

	private $builder;

	private $connection;
}