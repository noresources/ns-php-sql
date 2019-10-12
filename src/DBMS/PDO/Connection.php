<?php

// NAmespace
namespace NoreSources\SQL\PDO;

// Aliases
use NoreSources as ns;
use NoreSources\SQL as sql;
use NoreSources\SQL\PDO\Constants as K;

/**
 * PDO connection
 */
class Connection implements sql\Connection
{

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
	 * @param sql\StatementData|string $statement
	 * @return \NoreSources\SQL\PDO\PreparedStatement
	 */
	public function prepareStatement($statement)
	{
		if (!($this->connection instanceof \PDO))
			throw new sql\ConnectionException($this, 'Not connected');

		$pdo = $this->connection->prepare($statement, [
			\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL
		]);
		if (!($pdo instanceof \PDOStatement))
			throw new sql\ConnectionException($this, 'Failed to prepare statement');

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

		if ($parameters instanceof sql\ParameterArray && $parameters->count())
		{
			$checkParameters = false;

			if ($statement instanceof PreparedStatement)
			{
				if ($statement->isPDOStatementAcquired())
					throw new sql\ConnectionException($this,
						'Prepared statement is acquired by another object');

				$checkParameters = true;
			}
			else
			{
				$statement = $this->prepareStatement($statement);
			}

			$pdo = $statement->getPDOStatement();
			$pdo->closeCursor();

			foreach ($parameters as $key => $entry)
			{
				$name = '';
				if ($checkParameters)
				{
					if ($statement->getParameters()->offsetExists($key))
						$name = $statement->getParameters()->offsetGet($key);
					else
						throw new ConnectionException($this,
							'Parameter "' . $key . '" not found in prepared statement');
				}
				else
				{
					$name = ':' . $key;
				}

				$value = ns\Container::keyValue($entry, sql\ParameterArray::VALUE, null);
				$pdo->bindValue($name, $value);
			}

			$result = $statement->execute();
			if ($result === false)
				throw new sql\ConnectionException($this, 'Failed to execute');
		}
		else
		{
			$pdo = $this->connection->query($statement);
			if ($pdo === false)
				throw new sql\ConnectionException($this, 'Failed to execute');
			$statement = new PreparedStatement($pdo, $statement);
		}

		return (new Recordset($statement));
	}

	public function getPDOAttribute($attribute)
	{
		if (!($this->connection instanceof \PDO))
			throw new sql\ConnectionException($this, 'Not connected');

		return $this->connection->getAttribute($attribute);
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