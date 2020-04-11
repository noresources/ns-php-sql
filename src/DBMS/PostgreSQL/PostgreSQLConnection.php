<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\PostgreSQL;

use NoreSources\Container;
use NoreSources\SemanticVersion;
use NoreSources\TypeDescription;
use NoreSources\SQL\ParameterValue;
use NoreSources\SQL\DBMS\ConnectionException;
use NoreSources\SQL\DBMS\ConnectionHelper;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConstants as K;
use NoreSources\SQL\QueryResult\GenericInsertionQueryResult;
use NoreSources\SQL\QueryResult\GenericRowModificationQueryResult;
use NoreSources\SQL\Statement\ClassMapStatementFactory;
use NoreSources\SQL\Statement\InputData;
use NoreSources\SQL\Statement\ParameterData;
use NoreSources\SQL\Statement\ParametrizedStatement;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Statement\StatementFactoryInterface;
use NoreSources\SQL\Structure\StructureAwareTrait;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class PostgreSQLConnection implements ConnectionInterface
{

	use StructureAwareTrait;
	use LoggerAwareTrait;

	/**
	 *
	 * @var string
	 */
	const DEFAULT_VERSION = '9.0.0';

	public function __construct()
	{
		$this->resource = null;
		$this->builder = new PostgreSQLStatementBuilder($this);
		$this->versions = [
			self::VERSION_EXPECTED => new SemanticVersion(self::DEFAULT_VERSION),
			self::VERSION_CONNECTION => null
		];
	}

	public function __destruct()
	{
		if (\is_resource($this->resource))
			\pg_close($this->resource);
	}

	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;
		$this->getStatementBuilder()->setLogger($logger);
	}

	public function beginTransation()
	{
		return ($this->executeInstruction('BEGIN') !== false);
	}

	public function commitTransation()
	{
		return $this->executeInstruction('COMMIT') !== false;
	}

	public function rollbackTransaction()
	{
		return $this->executeInstruction('ROLLBACK') !== false;
	}

	public function connect($parameters)
	{
		if (\is_resource($this->resource))
			$this->disconnect();

		if (Container::keyExists($parameters, K::CONNECTION_VERSION))
			$this->versions[self::VERSION_EXPECTED] = new SemanticVersion(
				Container::keyValue($parameters, K::CONNECTION_VERSION));

		$dsn = [];
		foreach ([
			K::CONNECTION_SOURCE => 'host',
			K::CONNECTION_PORT => 'port',
			K::CONNECTION_DATABASE => 'dbname',
			K::CONNECTION_USER => 'user',
			K::CONNECTION_PASSWORD => 'password'
		] as $key => $name)
		{
			if (Container::keyExists($parameters, $key))
				$dsn[$name] = Container::keyValue($container, $key);
		}

		$dsn = Container::implode($dsn, ' ', function ($k, $v) {
			return $k . " = '" . $v . "'";
		});

		if (Container::keyExists($parameters, K::CONNECTION_PGSQL))
		{
			$value = Container::keyValue($parameters, K::CONNECTION_PGSQL, $key);
			if (\is_string($value))
				$dsn .= ' ' . $value;
			elseif (Container::isTraversable($value))
			{
				$dsn .= ' ' .
					Container::implode($value, ' ',
						function ($k, $v) {
							return $k . " = '" . $v . "'";
						});
			}
		}

		$connectionFunction = Container::keyValue($parameters, K::CONNECTION_PERSISTENT, false) ? '\pg_pconnect' : '\pg_connect';

		$this->resource = @call_user_func($connectionFunction, $dsn);

		if (!\is_resource($this->resource))
			throw new ConnectionException($this, 'Failed to connect');

		$info = \pg_version($this->resource);
		if (\preg_match('/^[0-9]+(\.[0-9]+)*/', $info['server'], $m))
		{
			$this->versions[self::VERSION_CONNECTION] = new SemanticVersion($m[0]);
		}

		$this->builder->updateBuilderFlags($this->getPostgreSQLVersion());
	}

	public function isConnected()
	{
		return (\is_resource($this->resource) &&
			(\pg_connection_status($this->resource) == PGSQL_CONNECTION_OK));
	}

	public function disconnect()
	{
		if (\is_resource($this->resource))
			\pg_close($this->resource);
		$this->versions[self::VERSION_CONNECTION] = null;
		$this->builder->updateBuilderFlags($this->getPostgreSQLVersion());
		$this->resource = null;
	}

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
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\DBMS\ConnectionInterface::prepareStatement()
	 */
	public function prepareStatement($statement)
	{
		$this->checkConnection();
		$identifier = PostgreSQLPreparedStatement::newUniqueId();
		$result = \pg_prepare($this->resource, $identifier, \strval($statement));
		$status = \pg_result_status($result);
		if (pg_result_status($result) != PGSQL_COMMAND_OK)
			throw new ConnectionException($this,
				'Failed to prepare statement. ' . \pg_result_error($result));

		return new PostgreSQLPreparedStatement($identifier, $statement);
	}

	public function executeStatement($statement, $parameters = array())
	{
		$this->checkConnection();

		if (!($statement instanceof PostgreSQLPreparedStatement ||
			TypeDescription::hasStringRepresentation($statement)))
			throw new ConnectionException($this,
				'Invalide statement type. string or ' . PostgreSQLStatementBuilder::class .
				' expected. Got ' . TypeDescription::getName($statement));

		$statementType = Statement::statementTypeFromData($statement);
		$pgResult = null;
		if (Container::count($parameters))
		{
			if ($statement instanceof PostgreSQLPreparedStatement)
			{
				$result = \pg_execute($this->resource, $statement->getPreparedStatementId(),
					self::getPostgreSQLParameterArray($statement, $parameters));
			}
			else
				$result = \pg_query_params($this->resource, $statement,
					self::getPostgreSQLParameterArray($statement, $parameters));
		}
		else
			$result = @\pg_query($this->resource, \strval($statement));

		if ($result === false)
			throw new ConnectionException($this, \pg_last_error($this->resource));

		if (!\is_resource($result))
			throw new \RuntimeException('Not implemented');

		$status = \pg_result_status($result);
		switch ($status)
		{
			case PGSQL_TUPLES_OK:
				$recordset = new PostgreSQLRecordset($result, $statement);
				$recordset->setDataUnserializer(PostgreSQLDataUnserializer::getInstance());
				return $recordset;
			break;

			case PGSQL_COMMAND_OK:
				if ($statementType & K::QUERY_FAMILY_ROWMODIFICATION)
					return new GenericRowModificationQueryResult(\pg_affected_rows($result));
				elseif ($statementType == K::QUERY_INSERT)
				{
					return new GenericInsertionQueryResult($this->getLastInsertId());
				}

				return true;

			break;
			case PGSQL_COPY_IN:
			case PGSQL_COPY_OUT:
				/**
				 *
				 * @todo
				 */
				return true;
			break;
			default:

			break;
		}

		// Unknown result type
		throw new ConnectionException($this, \pg_result_status($result, PGSQL_STATUS_STRING));
	}

	public function getPostgreSQLVersion()
	{
		if ($this->versions[self::VERSION_CONNECTION] instanceof SemanticVersion)
			return $this->versions[self::VERSION_CONNECTION];
		return $this->versions[self::VERSION_EXPECTED];
	}

	public function getConnectionResource()
	{
		return $this->resource;
	}

	private static function getPostgreSQLParameterArray($statement, $parameters = array())
	{
		$a = [];
		$indexed = Container::isIndexed($parameters);
		if ($indexed) // Don not use names...
		{
			foreach ($parameters as $entry)
			{
				$a[] = ConnectionHelper::serializeParameterValue($this, $entry);
			}

			return $a;
		}

		if ($statement instanceof ParametrizedStatement)
		{
			$map = $statement->getParameters();
			foreach ($map->getKeyIterator() as $key => $data)
			{
				foreach ($data[ParameterData::POSITIONS] as $index)
				{
					$p = $map->get($index);
					$dbmsName = $p[ParameterData::DBMSNAME];
					$entry = Container::keyValue($parameters, $key, null);
					$value = ($entry instanceof ParameterValue) ? $entry->value : $entry;
					$a[$index] = $value;
				}
			}

			ksort($a);
		}
		else
			throw \InvalidArgumentException(
				'Invalid parameter list. Indexed array is mandatory if the statement does not implement ' .
				InputData::class);

		return $a;
	}

	private function executeInstruction($sql)
	{
		$this->checkConnection();
		$result = \pg_query($this->resource, $sql);
		if ($result === false)
			throw new ConnectionException($this, \pg_last_error($this->resource));
		return $result;
	}

	private function checkConnection()
	{
		if (!\is_resource($this->resource))
			throw new ConnectionException($this, 'Not connected');
	}

	/**
	 * Get the last OID or the last SERIAL generated
	 *
	 * @return NULL|number|string
	 *
	 * @see https://www.php.net/manual/en/function.pg-last-oid.php
	 */
	private function getLastInsertId()
	{
		$id = null;
		if (SemanticVersion::compareVersions($this->getPostgreSQLVersion(), '7.2.0') < 0)
		{
			$id = \pg_last_oid();
		}
		elseif (SemanticVersion::compareVersions($this->getPostgreSQLVersion(), '8.1.0') > 0)
		{
			$savePointKey = '_NS_PHP_SQL_LASTVAL_SAVEPOINT_';
			$transaction = @(pg_transaction_status($this->resource) !== PGSQL_TRANSACTION_IDLE);

			if ($transaction)
				@pg_query('SAVEPOINT ' . $savePointKey);
			$result = @\pg_query('SELECT LASTVAL()');
			if (\is_resource($result) && pg_result_status($result, PGSQL_TUPLES_OK))
			{
				$id = \intval(\pg_fetch_result($result, 0, 0));
			}

			if ($transaction)
			{
				@pg_query('RELEASE ' . $savePointKey);
				@pg_query('ROLLBACK TO SAVEPOINT ' . $savePointKey);
			}
		}

		return $id;
	}

	/**
	 *
	 * @var resource
	 */
	private $resource;

	/**
	 *
	 * @var PostgreSQLStatementBuilder
	 */
	private $builder;

	/**
	 *
	 * @var \NoreSources\SQL\Statement\StatementFactoryInterface
	 */
	private $statementFactory;

	const VERSION_EXPECTED = 0;

	const VERSION_CONNECTION = 1;

	/**
	 *
	 * @var SemanticVersion[]
	 */
	private $versions;
}