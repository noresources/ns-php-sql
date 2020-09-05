<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\PDO;

use NoreSources\Container;
use NoreSources\TypeDescription;
use NoreSources\SQL\DBMS\ConnectionException;
use NoreSources\SQL\DBMS\ConnectionHelper;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\TransactionStackTrait;
use NoreSources\SQL\DBMS\PDO\PDOConstants as K;
use NoreSources\SQL\DBMS\Reference\ReferenceTransactionBlock;
use NoreSources\SQL\Result\GenericInsertionStatementResult;
use NoreSources\SQL\Result\GenericRowModificationStatementResult;
use NoreSources\SQL\Statement\ClassMapStatementFactory;
use NoreSources\SQL\Statement\ParameterData;
use NoreSources\SQL\Statement\ParameterDataProviderInterface;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Statement\StatementFactoryInterface;
use NoreSources\SQL\Structure\StructureProviderTrait;
use Psr\Log\LoggerAwareTrait;
use NoreSources\SQL\Structure\StructureElementInterface;

/**
 * PDO connection
 */
class PDOConnection implements ConnectionInterface
{
	use StructureProviderTrait;
	use LoggerAwareTrait;
	use TransactionStackTrait;

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
		return Container::implode($array, ':',
			function ($k, $v) {
				if (\is_integer($k))
					return $v;
				else
					return $k . '=' . $v;
			});
	}

	/**
	 *
	 * @param array $parameters
	 *        	Parameters array. Supported parameters are
	 *        	<ul>
	 *        	<li>CONNECTION_SOURCE</li>
	 *        	<li>CONNECTION_USER</li>
	 *        	<li>CONNECTION_PASSWORD</li>
	 *        	<li>CONNECTION_OPTIONS</li>
	 *        	</ul>
	 *
	 */
	public function __construct($parameters)
	{
		$this->builder = new PDOStatementBuilder($this);
		$this->connection = null;
		$this->setTransactionBlockFactory(
			function ($depth, $name) {
				return new ReferenceTransactionBlock($this, $name);
			});

		if ($this->connection instanceof \PDO)
			$this->connection->close();

		$structure = Container::keyValue($parameters, K::CONNECTION_STRUCTURE);
		if ($structure instanceof StructureElementInterface)
			$this->setStructure($structure);

		$dsn = Container::keyValue($parameters, K::CONNECTION_SOURCE, null);
		$user = Container::keyValue($parameters, K::CONNECTION_USER, null);
		$password = Container::keyValue($parameters, K::CONNECTION_PASSWORD, null);
		$options = Container::keyValue($parameters, K::CONNECTION_OPTIONS, null);

		if (Container::isArray($dsn))
		{
			$dsn = self::buildDSN($dsn);
		}

		if (!\is_string($dsn))
			throw new ConnectionException($this,
				'Invalid DSN parameter. string or array expected. Got ' .
				TypeDescription::getName($dsn));

		try
		{
			$this->connection = new \PDO($dsn, $user, $password, $options);
			$this->builder->configure($this->connection);
		}
		catch (\PDOException $e)
		{
			throw new ConnectionException($this, $e->getMessage(), $e->getCode());
		}

		if (Container::keyExists($parameters, K::CONNECTION_STRUCTURE))
			$this->setStructure($structure)[K::CONNECTION_STRUCTURE];
		;
	}

	public function __destruct()
	{
		$this->endTransactions(false);
		unset($this->connection);
		$this->connection = null;
	}

	public function isConnected()
	{
		if (!($this->connection instanceof \PDO))
			return false;

		$status = true;
		try
		{
			$status = $this->connection->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
		}
		catch (\Exception $e)
		{}

		return $status;
	}

	public function newTransactionBlock($name = null)
	{
		return null;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\DBMS\ConnectionInterface::getStatementBuilder()
	 */
	public function getStatementBuilder()
	{
		return $this->builder;
	}

	public function getStatementFactory()
	{
		if (!($this->statementFactory instanceof StatementFactoryInterface))
		{
			$this->statementFactory = new ClassMapStatementFactory();
		}

		return $this->statementFactory;
	}

	/**
	 *
	 * @param SQL\BuildContext|string $statement
	 * @return \NoreSources\SQL\DBMS\PDO\PDOPreparedStatement
	 */
	public function prepareStatement($statement)
	{
		if (!($this->connection instanceof \PDO))
			throw new ConnectionException($this, 'Not connected');

		$type = Statement::statementTypeFromData($statement);
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
			throw new ConnectionException($this, 'Failed to prepare statement. ' . $message);
		}

		return new PDOPreparedStatement($pdo, $statement);
	}

	public function executeStatement($statement, $parameters = array())
	{
		if (!($this->connection instanceof \PDO))
			throw new ConnectionException($this, 'Not connected');

		if (!(($statement instanceof PDOPreparedStatetement) ||
			TypeDescription::hasStringRepresentation($statement)))
		{
			throw new \InvalidArgumentException(
				'Invalid type ' . TypeDescription::getName($statement) .
				' for statement argument. string or ' . PDOPreparedStatement::class . ' expected');
		}

		$pdo = null;
		$prepared = null;

		if (Container::count($parameters))
		{
			if ($statement instanceof PDOPreparedStatement)
			{
				if ($statement->isPDOStatementAcquired())
					throw new ConnectionException($this,
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
				$dbmsName = '';
				if ($statement instanceof ParameterDataProviderInterface)
					$dbmsName = $statement->getParameters()->get($key)[ParameterData::DBMSNAME];
				else
					$dbmsName = ':' . $key;

				$value = ConnectionHelper::serializeParameterValue($this, $entry);
				$pdo->bindValue($dbmsName, $value);
			}

			$result = $pdo->execute();
			if ($result === false)
			{
				$error = $pdo->errorInfo();
				$message = self::getErrorMessage($error);
				throw new ConnectionException($this, 'Failed to execute');
			}
		}
		else // Basic case
		{
			$pdo = $this->connection->query($statement);
			if ($pdo === false)
				throw new ConnectionException($this, 'Failed to execute');

			$prepared = new PDOPreparedStatement($pdo, $statement);
		}

		/**
		 *
		 * @var PDOPreparedStatement $prepared
		 */

		$result = true;
		$type = Statement::statementTypeFromData($prepared);

		if ($type == K::QUERY_SELECT)
			$result = (new PDORecordset($prepared));
		elseif ($type == K::QUERY_INSERT)
			$result = new GenericInsertionStatementResult($this->connection->lastInsertId());
		elseif ($type & K::QUERY_FAMILY_ROWMODIFICATION)
			$result = new GenericRowModificationStatementResult($pdo->rowCount());

		return $result;
	}

	public static function getErrorMessage($error)
	{
		return Container::implode($error, ', ',
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
			throw new ConnectionException($this, 'Not connected');

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

	public function getConnectionObject()
	{
		return $this->connection;
	}

	/**
	 *
	 * @var PDOStatementBuilder
	 */
	private $builder;

	/**
	 *
	 * @var \NoreSources\SQL\Statement\StatementFactoryInterface
	 */
	private $statementFactory;

	/**
	 * DBMS connection
	 *
	 * @var \PDO
	 */
	private $connection;
}