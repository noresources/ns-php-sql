<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\MySQL;

// Aliases
use NoreSources\Container;
use NoreSources\TypeDescription;
use NoreSources\SQL\DBMS\Connection;
use NoreSources\SQL\DBMS\ConnectionException;
use NoreSources\SQL\DBMS\ConnectionStructureTrait;
use NoreSources\SQL\DBMS\MySQL\MySQLConstants as K;
use NoreSources\SQL\QueryResult\GenericInsertionQueryResult;
use NoreSources\SQL\QueryResult\GenericRowModificationQueryResult;
use NoreSources\SQL\Statement\Statement;

/**
 * MySQL or MariaDB connection
 */
class MySQLConnection implements Connection
{
	use ConnectionStructureTrait;

	const STATE_CONNECTED = 0x01;

	public function __construct()
	{
		$this->mysqlFlags = 0;
		$this->link = new \mysqli();
		$this->builder = new MySQLStatementBuilder($this);
	}

	public function __destruct()
	{
		if ($this->mysqlFlags & self::STATE_CONNECTED)
			$this->link->close();
	}

	public function beginTransation()
	{
		return $this->link->begin_transaction();
	}

	public function commitTransation()
	{
		return $this->link->commit();
	}

	public function rollbackTransaction()
	{
		return $this->link->rollback();
	}

	/**
	 *
	 * @return boolean
	 */
	public function isConnected()
	{
		return (($this->mysqlFlags & self::STATE_CONNECTED) == self::STATE_CONNECTED);
	}

	public function connect($parameters)
	{
		if ($this->isConnected())
			$this->disconnect();

		$persistent = Container::keyValue($parameters, K::CONNECTION_PARAMETER_PERSISTENT, false);
		$protocol = Container::keyValue($parameters, K::CONNECTION_PARAMETER_PROTOCOL,
			K::CONNECTION_PARAMETER_PROTOCOL_TCP);

		if ($protocol == K::CONNECTION_PARAMETER_PROTOCOL_TCP)
		{
			$host = Container::keyValue($parameters, K::CONNECTION_PARAMETER_SOURCE,
				ini_get('mysqli.default_host'));
			$port = Container::keyValue($parameters, K::CONNECTION_PARAMETER_PORT,
				ini_get('mysqli.default_port'));
			$user = Container::keyValue($parameters, K::CONNECTION_PARAMETER_USER,
				ini_get('mysqli.default_user'));
			$password = Container::keyValue($parameters, K::CONNECTION_PARAMETER_PASSWORD,
				ini_get('mysqli.default_pw'));
			$database = Container::keyValue($parameters, K::CONNECTION_PARAMETER_DATABASE, '');

			if ($persistent && (substr($host, 0, 2) != 'p:'))
				$host = 'p:' . $host;

			$this->link->connect($host, $user, $password, $database, $port);
		}
		else // Socket or pipe
		{
			$path = Container::keyValue($parameters, K::CONNECTION_PARAMETER_SOURCE,
				ini_get("mysqli.default_socket"));
			if ($persistent && (substr($path, 0, 2) != 'p:'))
				$path = 'p:' . $path;

			$this->link->connect($path);
		}

		/**
		 *
		 * @todo throw on error
		 */

		if ($this->link->connect_errno === 0)
		{
			$this->mysqlFlags |= self::STATE_CONNECTED;
		}

		return $this->isConnected();
	}

	public function disconnect()
	{
		if ($this->mysqlFlags & self::STATE_CONNECTED)
			$this->link->close();

		$this->mysqlFlags &= ~self::STATE_CONNECTED;
	}

	public function getStatementBuilder()
	{
		return $this->builder;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\DBMS\Connection::prepareStatement()
	 *
	 * @return MySQLPreparedStatement
	 */
	public function prepareStatement($statement)
	{
		$stmt = $this->link->stmt_init();
		$stmt->prepare(\strval($statement));
		/**
		 *
		 * @todo error management
		 */

		return new MySQLPreparedStatement($stmt, $statement);
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\DBMS\Connection::executeStatement()
	 */
	public function executeStatement($statement, $parameters = array())
	{
		if (!($statement instanceof MySQLPreparedStatement ||
			TypeDescription::hasStringRepresentation($statement)))
			throw new ConnectionException($this,
				'Invalid statement type ' . TypeDescription::getName($statement) .
				'. Expect PreparedStatement or stringifiable');
		;

		$result = null;
		$statementType = Statement::statementTypeFromData($statement);
		$stmt = null;
		$prepared = ($statement instanceof MySQLPreparedStatement) ? $statement : $this->prepareStatement(
			$statement);
		$stmt = $prepared->getMySQLStmt();

		$result = null;
		if (Container::count($parameters))
		{
			/*
			 $bindArguments = [];
			 $bindArguments[0] = '';
			 $map = $prepared->getParameters();
			 $iterator = $map->getIndexedParameterIterator();

			 foreach ($iterator as $index => $p)
			 {
			 var_dump($index . ' ' . $p);
			 }
			 */
			throw new \Exception('Not implemented');
		}

		$result = false;
		$success = @$stmt->execute();
		if ($success)
		{
			/**
			 * Returns a resultset for successful SELECT queries, or FALSE for other DML queries or on failure.
			 * The mysqli_errno() function can be used to distinguish between the two types of failure.
			 */
			$result = $stmt->get_result();
		}

		if (!$success || ($result === false && $stmt->errno != 0))
			throw new ConnectionException($this, $stmt->error);

		if ($result instanceof \mysqli_result)
		{
			return new MySQLRecordset($result, $statement);
		}
		elseif ($statementType & K::QUERY_FAMILY_ROWMODIFICATION)
		{
			return new GenericRowModificationQueryResult($stmt->affected_rows);
		}
		elseif ($statementType == K::QUERY_INSERT)
		{
			return new GenericInsertionQueryResult($stmt->insert_id);
		}

		return true;
	}

	/**
	 *
	 * @return mysqli
	 */
	public function getServerLink()
	{
		return $this->link;
	}

	/**
	 *
	 * @var MySQLStatementBuilder
	 */
	private $builder;

	/**
	 *
	 * @var \mysqli
	 */
	private $link;

	/**
	 *
	 * @var integer
	 */
	private $mysqlFlags;
}