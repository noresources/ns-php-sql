<?php

// NAmespace
namespace NoreSources\SQL\SQLite;

// Aliases
use NoreSources as ns;
use NoreSources\SQL as sql;
use NoreSources\SQL\SQLite\Constants as K;

class ConnectionException extends sql\ConnectionException
{

	public function __construct(Connection $connection, $message, $code = null)
	{
		if ($code === null)
		{
			$code = $connection->sqliteConnection->lastErrorCode();
			if ($code != 0)
				$message .= ' (' . $connection->sqliteConnection->lastErrorMsg() . ')';
		}
		parent::__construct($connection, $message, $code);
	}
}

/**
 * SQLite connection
 */
class Connection implements sql\Connection
{
	use sql\ConnectionStructureTrait;

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
		$this->builder = new StatementBuilder();
		$this->connection = null;
	}

	public function __destruct()
	{
		if ($this->connection instanceof \SQLite3)
			$this->disconnect();
	}

	public function __get($member)
	{
		if ($member == 'sqliteConnection')
			return $this->connection;

		return parent::__get($key);
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

		$pragmas = ns\Container::keyValue($parameters, K::CONNECTION_PARAMETER_SQLITE_PRAGMAS,
			[
				'foreign_keys' => 1,
				'busy_timeout' => 5000
			]);

		$defaultTablesetName = ns\Container::keyValue($parameters, K::CONNECTION_PARAMETER_DATABASE,
			self::TABLESET_NAME_DEFAULT);

		$sources = ns\Container::keyValue($parameters, K::CONNECTION_PARAMETER_SOURCE,
			[
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
				throw new sql\ConnectionException($this, 'Duplicated tableset name ' . $name);
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
				$result = @$this->connection->exec($sql);
				if ($result === false)
					throw new ConnectionException($this, 'Failed to attach database');
			}
		}

		foreach ($pragmas as $pragma => $value)
		{
			$result = $this->connection->exec('PRAGMA ' . $pragma . '=' . $value);
			if ($result === false)
				throw new ConnectionException($this, 'Failed to set ' . $pragma . ' pragma');
		}

		if (ns\Container::keyExists($parameters, K::CONNECTION_PARAMETER_STRUCTURE))
			$this->setStructure($structure)[K::CONNECTION_PARAMETER_STRUCTURE];
	}

	public function disconnect()
	{
		if (!($this->connection instanceof \SQLite3))
			throw new sql\ConnectionException($this, 'Not connected');
		$this->connection->close();
		$this->connection = null;
	}

	public function getStatementBuilder()
	{
		return $this->builder;
	}

	/**
	 *
	 * @param sql\BuildContext|string $statement
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
	 * @param \NoreSources\SQL\StatementParameterArray $parameters
	 */
	public function executeStatement($statement, sql\StatementParameterArray $parameters = null)
	{
		if (!($this->connection instanceof \SQLite3))
			throw new sql\ConnectionException($this, 'Not connected');

		if (!($statement instanceof PreparedStatement ||
			ns\TypeDescription::hasStringRepresentation($statement)))
			throw new sql\ConnectionException($this,
				'Invalid statement type ' . ns\TypeDescription::getName($statement) .
				'. Expect PreparedStatement or stringifiable');
		;

		$result = null;
		$statementType = sql\Statement::statementTypeFromData($statement);

		if ($parameters instanceof sql\StatementParameterArray && $parameters->count())
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
				if ($statement instanceof sql\Statement\InputData)
				{
					if ($statement->hasParameter($key))
						$name = $statement->getParameter($key);
					else
						throw new sql\ConnectionException($this,
							'Parameter "' . $key . '" not found in prepared statement (with ' .
							$statement->getParameterCount() . ' parameter(s))');
				}
				else
				{
					$name = $this->getStatementBuilder()->getParameter($key, -1);
				}

				$value = ns\Container::keyValue($entry, sql\StatementParameterArray::VALUE, null);
				$type = ns\Container::keyValue($entry, sql\StatementParameterArray::TYPE,
					K::DATATYPE_UNDEFINED);

				$type = self::sqliteDataTypeFromDataType($type);
				$bindResult = $stmt->bindValue($name, $value, $type);
				if (!$bindResult)
					throw new sql\ConnectionException($this, 'Failed to bind "' . $name . '"');
			}

			$result = @$stmt->execute();
		}
		else
		{
			if ($statementType != K::QUERY_SELECT)
				$result = @$this->connection->exec(strval($statement));
			else
				$result = @$this->connection->query(strval($statement));
		}

		if ($result === false)
			throw new ConnectionException($this,
				'Failed to execute statement of type ' . $statementType);

		if ($statementType & K::QUERY_FAMILY_ROWMODIFICATION)
		{
			if (($result instanceof \SQLite3Result) || $result)
			{
				return new sql\GenericRowModificationQueryResult($this->connection->changes());
			}
		}
		elseif ($statementType == K::QUERY_INSERT)
		{
			if (($result instanceof \SQLite3Result) || $result)
			{
				return new sql\GenericInsertionQueryResult($this->connection->lastInsertRowID());
			}
		}
		elseif ($statementType == K::QUERY_SELECT)
		{
			if ($result instanceof \SQLite3Result)
			{
				return new Recordset($result, $statement);
			}
		}
		else
		{
			return (($result instanceof \SQLite3Result) || $result);
		}

		throw new ConnectionException($this, 'Failed to execute statement of type ' . $statementType);
	}

	/**
	 *
	 * @param integer $sqlType
	 * @return integer The SQLITE_* type corresponding to the given \NoreSOurce\SQL data type
	 */
	public static function sqliteDataTypeFromDataType($sqlType)
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

	public static function dataTypeFromSQLiteDataType($sqliteType)
	{
		switch ($sqliteType)
		{
			case \SQLITE3_BLOB:
				return K::DATATYPE_BINARY;
			case \SQLITE3_FLOAT:
				return K::DATATYPE_FLOAT;
			case \SQLITE3_INTEGER:
				return K::DATATYPE_INTEGER;
			case \SQLITE3_NULL:
				return K::DATATYPE_NULL;
		}

		return K::DATATYPE_STRING;
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