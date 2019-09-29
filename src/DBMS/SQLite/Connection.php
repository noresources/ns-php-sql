<?php

// NAmespace
namespace NoreSources\SQL\SQLite;

// Aliases
use NoreSources as ns;
use NoreSources\SQL as sql;
use NoreSources\SQL\SQLite\Constants as K;
use NoreSources\SQL\ConnectionException;

/**
 * SQLite connection
 */
class Connection implements sql\Connection
{

	/**
	 * Special in-memory database name
	 *
	 * @see https://www.sqlite.org/inmemorydb.html
	 *
	 * @var string
	 */
	const SOURCE_MEMORY = ':memory:';

	/**
	 * Temporary database.
	 *
	 * @see https://www.sqlite.org/inmemorydb.html
	 * @var string
	 */
	const SOURCE_TEMPORARY = '';

	/**
	 * The default tableset name
	 *
	 * @var string
	 */
	const TABLESET_NAME_DEFAULT = 'main';

	public function __construct()
	{
		$this->builder = new StatementBuilder(new sql\ExpressionEvaluator());
		$this->connection = null;
	}

	public function __destruct()
	{
		if ($this->connection instanceof \SQLite3)
			$this->disconnect();
	}

	public function beginTransation()
	{}

	public function commitTransation()
	{}

	public function rollbackTransaction()
	{}

	/**
	 * Connect to DBMS
	 *
	 * @param \ArrayAccess $parameters
	 *        	Connection parameters. Accepted keys are
	 *        	<ul>
	 *        	<li>CONNECTION_PARAMETER_SOURCE (string|array):
	 *        	<ul>
	 *        	<li>If unspecified, use a in-memory storage</li>
	 *        	<li>If the parameter value is a string, the database will be loaded as the "main" database</li>
	 *        	<li>If the parameter value is an array, the elements key represents the tableset name,
	 *        	the values represents the database
	 *        	file name. If the key is not a string, the base file name is used as tableet name</li>
	 *        	</ul>
	 *        	<li>CONNECTION_PARAMETER_DATABASE (string): Overrides the tableset name if CONNECTION_PARAMETER_SOURCE value is a
	 *        	string</li>
	 *        	<li>CONNECTION_PARAMETER_CREATE (bool): Create database file if it does not exists</li>
	 *        	<li>CONNECTION_PARAMETER_READONLY (bool): Indicates the database is read only</li>
	 *        	<li>CONNECTION_PARAMETER_ENCRYPTION_KEY (string): Database encryption key</li>
	 *        	</ul>
	 */
	public function connect($parameters)
	{
		if ($this->connection instanceof \SQLite3)
			$this->connection->close();

		$this->connection = null;

		$defaultTablesetName = ns\Container::keyValue($parameters, K::CONNECTION_PARAMETER_DATABASE,
			self::TABLESET_NAME_DEFAULT);

		$sources = ns\Container::keyValue($parameters, K::CONNECTION_PARAMETER_SOURCE, [
				$defaultTablesetName => self::SOURCE_MEMORY
			]);

		$flags = 0;
		if (ns\Container::keyValue($parameters, K::CONNECTION_PARAMETER_READONLY, false))
		{
			$flags |= \SQLITE3_OPEN_READONLY;
		}
		else
		{
			$flags |= \SQLITE3_OPEN_READWRITE;
		}

		if (ns\Container::keyValue($parameters, K::CONNECTION_PARAMETER_CREATE, false))
		{
			if ($flags & \SQLITE3_OPEN_READONLY)
			{
				throw new sql\ConnectionException($this,
					'Unable to set Auto-create and Read only flags at the same time');
			}

			$flags |= \SQLITE3_OPEN_CREATE;
		}

		if (\is_string($sources))
		{
			$sources = [
				$defaultTablesetName => $sources
			];
		}

		$names = [];
		foreach ($sources as $name => $source)
		{
			$name = self::getTablesetName($name, $source);

			if (\in_array($name, $names))
			{
				throw new ConnectionException($this, 'Duplicated tableset name ' . $name);
			}

			$names[] = $name;

			$attach = false;
			$sql = 'ATTACH DATABASE \'' . $this->builder->escapeString($source) . '\' AS ' .
				$this->builder->escapeIdentifier($name);

			if ($this->connection instanceof \SQLite3)
			{
				$attach = true;
			}
			else
			{
				$key = ns\Container::keyValue($parameters, K::CONNECTION_PARAMETER_ENCRYPTION_KEY,
					null);
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
	}

	public function disconnect()
	{
		if (!($this->connection instanceof \SQLite3))
			throw new sql\ConnectionException($this, 'Not connected');
		$this->connection->close();
	}

	public function getStatementBuilder()
	{
		return $this->builder;
	}

	/**
	 *
	 * @param sql\StatementData|string $statement
	 * @return \NoreSources\SQL\SQLite\PreparedStatement
	 */
	public function prepareStatement($statement)
	{
		if (!($this->connection instanceof \SQLite3))
			throw new sql\ConnectionException($this, 'Not connected');

		$stmt = $this->connection->prepare($statement);
		if (!($stmt instanceof \SQLite3Stmt))
			throw new sql\ConnectionException($this, 'Unable to create SQLite statement');

		return new PreparedStatement($stmt, $statement);
	}

	/**
	 *
	 * @param
	 *        	PreparedStatement|string SQL statement
	 * @param \NoreSources\SQL\ParameterArray $parameters
	 */
	public function executeStatement($statement, sql\ParameterArray $parameters = null)
	{
		if (!($this->connection instanceof \SQLite3))
			throw new sql\ConnectionException($this, 'Not connected');

		$result = null;

		if ($parameters instanceof sql\ParameterArray && $parameters->count())
		{
			$stmt = null;
			if ($statement instanceof PreparedStatement)
			{
				$stmt = $statement->getSQLite3Stmt();
				$stmt->clear();
				$stmt->reset();
			}
			else
			{
				$stmt = $this->connection->prepare($statement);
			}

			foreach ($parameters as $key => $entry)
			{
				$name = $key;
				if ($statement instanceof PreparedStatement)
				{
					if ($statement->getParameters()->offsetExists($key))
						$name = $statement->getParameters()->offsetGet($key);
					else
						throw new ConnectionException($this,
							'Parameter "' . $key . '" not found in prepared statement');
				}
				else
				{
					$name = $this->getStatementBuilder()->getParameter($key, -1);
				}

				$value = ns\Container::keyValue($entry, sql\ParameterArray::VALUE, null);
				$type = ns\Container::keyValue($entry, sql\ParameterArray::TYPE,
					K::DATATYPE_UNDEFINED);

				$type = self::getSQLiteDataType($type);
				$bindResult = $stmt->bindValue($name, $value, $type);
				if (!$bindResult)
					throw new sql\ConnectionException($this, 'Failed to bind "' . $name . '"');
			}

			$result = $stmt->execute();
		}
		else
		{
			$result = $this->connection->query(strval($statement));
		}

		if ($result instanceof \SQLite3Result)
		{
			return new Recordset($result);
		}

		throw new sql\ConnectionException($this, 'Failed to execute');
	}

	/**
	 *
	 * @param integer $sqlType
	 * @return integer The SQLITE_* type corresponding to the given \NoreSOurce\SQL data type
	 */
	public static function getSQLiteDataType($sqlType)
	{
		switch ($sqlType)
		{
			case K::DATATYPE_BINARY:
				return \SQLITE3_BLOB;
			case K::DATATYPE_FLOAT:
				return \SQLITE3_FLOAT;
			case K::DATATYPE_NULL:
				return \SQLITE3_NULL;
			case K::DATATYPE_INTEGER:
			case K::DATATYPE_BOOLEAN:
				return \SQLITE3_INTEGER;
		}
		return \SQLITE3_TEXT;
	}

	/**
	 * Get a tableset name for the given database source
	 *
	 * @param mixed $name
	 *        	User-defined name
	 * @param string $source
	 *        	Database source
	 * @return string
	 */
	private static function getTablesetName($name, $source)
	{
		if (is_string($name) && strlen($name))
			return $name;

		if ($source == self::SOURCE_MEMORY || $source == self::SOURCE_TEMPORARY)
		{
			return self::TABLESET_NAME_DEFAULT;
		}

		return pathinfo($source, 'filename');
	}

	/**
	 *
	 * @var StatementBuilder
	 */
	private $builder;

	/**
	 * DBMS connection
	 *
	 * @var \SQLite3
	 */
	private $connection;
}