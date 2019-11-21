<?php

// NAmespace
namespace NoreSources\SQL\PDO;

// Aliases
use NoreSources as ns;
use NoreSources\SQL as sql;
use NoreSources\SQL\PDO\Constants as K;
use NoreSources\SQL\ConnectionHelper;
use NoreSources\TypeDescription;
use NoreSources\SQL\ConnectionStructureTrait;

/**
 * PDO connection
 */
class Connection implements sql\Connection
{
	use sql\ConnectionStructureTrait;

	const DRIVER_MYSQL = 'mysql';

	const DRIVER_POSTGRESQL = 'pgsql';

	const DRIVER_SQLITE = 'sqlite';

	/**
	 * Build a DSN string from an array of DSN parameters
	 *
	 * @param array $array
	 * @return string
	 */
	public static function buildDSN($array)
	{
		return ns\Container::implode($array, ':',
			function ($k, $v) {
				if (\is_integer($k))
					return $v;
				else
					return $k . '=' . $v;
			});
	}

	public function __construct()
	{
		$this->builder = new StatementBuilder($this);
		$this->connection = null;
	}

	public function __destruct()
	{
		if ($this->connection instanceof \PDO)
			$this->disconnect();
	}

	public function beginTransation()
	{
		$this->connection->beginTransaction();
	}

	public function commitTransation()
	{
		$this->connection->commit();
	}

	public function rollbackTransaction()
	{
		$this->connection->rollBack();
	}

	/**
	 *
	 * @param array $parameters
	 *        	Parameters array. Supported parameters are
	 *        	<ul>
	 *        	<li>CONNECTION_PARAMETER_SOURCE</li>
	 *        	<li>CONNECTION_PARAMETER_USER</li>
	 *        	<li>CONNECTION_PARAMETER_PASSWORD</li>
	 *        	<li>CONNECTION_PARAMETER_OPTIONS</li>
	 *        	</ul>
	 *        	
	 */
	public function connect($parameters)
	{
		if ($this->connection instanceof \PDO)
			$this->connection->close();

		$dsn = ns\Container::keyValue($parameters, K::CONNECTION_PARAMETER_SOURCE, null);
		$user = ns\Container::keyValue($parameters, K::CONNECTION_PARAMETER_USER, null);
		$password = ns\Container::keyValue($parameters, K::CONNECTION_PARAMETER_PASSWORD, null);
		$options = ns\Container::keyValue($parameters, K::CONNECTION_PARAMETER_OPTIONS, null);

		if (ns\Container::isArray($dsn))
		{
			$dsn = self::buildDSN($dsn);
		}

		if (!\is_string($dsn))
			throw new sql\ConnectionException($this,
				'Invalid DSN parameter. string or array expected. Got ' .
				ns\TypeDescription::getName($dsn));

		try
		{
			$this->connection = new \PDO($dsn, $user, $password, $options);
			$this->builder->configure($this->connection);
		}
		catch (\PDOException $e)
		{
			throw new sql\ConnectionException($this, $e->getMessage(), $e->getCode());
		}

		if (ns\Container::keyExists($parameters, K::CONNECTION_PARAMETER_STRUCTURE))
			$this->setStructure($structure)[K::CONNECTION_PARAMETER_STRUCTURE];
		;
	}

	public function disconnect()
	{
		$this->connection = null;
	}

	public function getStatementBuilder()
	{
		return $this->builder;
	}

	/**
	 *
	 * @param sql\StatementContext|string $statement
	 * @return \NoreSources\SQL\PDO\PreparedStatement
	 */
	public function prepareStatement($statement)
	{
		if (!($this->connection instanceof \PDO))
			throw new sql\ConnectionException($this, 'Not connected');

		$type = sql\Statement::statementTypeFromData($statement);
		$attributes = [];
		if ($type == K::QUERY_SELECT)
			$attributes[\PDO::ATTR_CURSOR] = \PDO::CURSOR_SCROLL;

		$pdo = $this->connection->prepare($statement, $attributes);
		if (!($pdo instanceof \PDOStatement))
			$pdo = $this->connection->prepare($statement);

		if (!($pdo instanceof \PDOStatement))
		{
			$error = $this->connection->errorInfo();
			$message = self::getErrorMessage($error);
			throw new sql\ConnectionException($this, 'Failed to prepare statement. ' . $message);
		}

		return new PreparedStatement($pdo, $statement);
	}

	/**
	 *
	 * @param
	 *        	PreparedStatement|string SQL statement
	 * @param \NoreSources\SQL\ParameterArray $parameters
	 */
	public function executeStatement($statement, sql\ParameterArray $parameters = null)
	{
		if (!($this->connection instanceof \PDO))
			throw new sql\ConnectionException($this, 'Not connected');

		if (!(($statement instanceof PreparedStatetement) ||
			TypeDescription::hasStringConversion($statement)))
		{
			throw new \InvalidArgumentException(
				'Invalid type ' . ns\TypeDescription::getName($statement) .
				' for statement argument. string or PDO\PreparedStatement expected');
		}

		$pdo = null;
		$prepared = null;

		if ($parameters instanceof sql\ParameterArray && $parameters->count())
		{
			if ($statement instanceof PreparedStatement)
			{
				if ($statement->isPDOStatementAcquired())
					throw new sql\ConnectionException($this,
						'Prepared statement is acquired by another object');

				$prepared = $statement;
			}
			else
			{
				$prepared = $this->prepareStatement($statement);
			}

			$pdo = $prepared->getPDOStatement();
			$pdo->closeCursor();

			foreach ($parameters as $key => $entry)
			{
				$name = '';
				if ($statement instanceof sql\StatementInputData)
				{
					if ($statement->hasParameter($key))
						$name = $statement->getParameter($key);
					else
						throw new sql\ConnectionException($this,
							'Parameter "' . $key . '" not found in prepared statement (with ' .
							$statement->getParameterCount() . ' parameter(s))');
				}
				else
					$name = ':' . $key;

				$value = ns\Container::keyValue($entry, sql\ParameterArray::VALUE, null);
				$pdo->bindValue($name, $value);
			}

			$result = $pdo->execute();
			if ($result === false)
			{
				$error = $pdo->errorInfo();
				$message = self::getErrorMessage($error);
				throw new sql\ConnectionException($this, 'Failed to execute');
			}
		}
		else // Basic case
		{
			$pdo = $this->connection->query($statement);
			if ($pdo === false)
				throw new sql\ConnectionException($this, 'Failed to execute');

			$prepared = new PreparedStatement($pdo, $statement);
		}

		/**
		 *
		 * @var PreparedStatement $prepared
		 */

		$result = true;
		$type = sql\Statement::statementTypeFromData($prepared);

		if ($type == K::QUERY_SELECT)
		{
			$result = (new Recordset($prepared));
		}
		elseif ($type == K::QUERY_INSERT)
		{
			$result = new sql\GenericInsertionQueryResult($this->connection->lastInsertId());
		}
		elseif ($type & K::QUERY_FAMILY_ROWMODIFICATION)
		{
			$result = new sql\GenericRowModificationQueryResult($pdo->rowCount());
		}

		return $result;
	}

	public static function getErrorMessage($error)
	{
		return ns\Container::implode($error, ', ',
			function ($k, $v) {
				if (strlen($v) == 0)
					return false;
				if ($k == 0) // SQLSTATE
					return 'Error code ' . $v;
				elseif ($k == 2) // Error message
					return $v;
				return false;
			});
	}

	public function getPDOAttribute($attribute)
	{
		if (!($this->connection instanceof \PDO))
			throw new sql\ConnectionException($this, 'Not connected');

		return $this->connection->getAttribute($attribute);
	}

	public static function getDataTypeFromPDOType($pdoType)
	{
		switch ($pdoType)
		{
			case \PDO::PARAM_BOOL:
				return K::DATATYPE_BOOLEAN;
			case \PDO::PARAM_NULL:
				return K::DATATYPE_NULL;
			case \PDO::PARAM_INT:
				return K::DATATYPE_INTEGER;
			case \PDO::PARAM_STR:
				return K::DATATYPE_STRING;
		}
		return K::DATATYPE_UNDEFINED;
	}

	/**
	 *
	 * @var StatementBuilder
	 */
	private $builder;

	/**
	 * DBMS connection
	 *
	 * @var \PDO
	 */
	private $connection;
}