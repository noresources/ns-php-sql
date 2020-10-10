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
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\IdentifierSerializerInterface;
use NoreSources\SQL\DBMS\PlatformProviderTrait;
use NoreSources\SQL\DBMS\StringSerializerInterface;
use NoreSources\SQL\DBMS\TransactionInterface;
use NoreSources\SQL\DBMS\TransactionStackTrait;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConstants as K;
use NoreSources\SQL\Result\GenericInsertionStatementResult;
use NoreSources\SQL\Result\GenericRowModificationStatementResult;
use NoreSources\SQL\Statement\ParameterData;
use NoreSources\SQL\Statement\ParameterDataProviderInterface;
use NoreSources\SQL\Statement\Statement;

class PostgreSQLConnection implements ConnectionInterface,
	TransactionInterface, StringSerializerInterface,
	IdentifierSerializerInterface
{

	use TransactionStackTrait;
	use PlatformProviderTrait;

	public function __construct($parameters)
	{
		$this->setTransactionBlockFactory(
			function ($depth, $name) {
				return new PostgreSQLTransactionBlock($this, $name);
			});
		$this->resource = null;

		$structure = Container::keyValue($parameters,
			K::CONNECTION_STRUCTURE);

		$dsn = [];
		foreach ([
			K::CONNECTION_SOURCE => 'host',
			K::CONNECTION_PORT => 'port',
			K::CONNECTION_USER => 'user',
			K::CONNECTION_PASSWORD => 'password'
		] as $key => $name)
		{
			if (Container::keyExists($parameters, $key))
				$dsn[$name] = Container::keyValue($container, $key);
		}

		$dsn = Container::implode($dsn, ' ',
			function ($k, $v) {
				return $k . " = '" . $v . "'";
			});

		if (Container::keyExists($parameters, K::CONNECTION_PGSQL))
		{
			$value = Container::keyValue($parameters,
				K::CONNECTION_PGSQL, $key);
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

		$connectionFunction = Container::keyValue($parameters,
			K::CONNECTION_PERSISTENT, false) ? '\pg_pconnect' : '\pg_connect';

		$this->resource = @call_user_func($connectionFunction, $dsn);

		if (!\is_resource($this->resource))
			throw new ConnectionException($this, 'Failed to connect');
	}

	public function __destruct()
	{
		$this->endTransactions(false);
		if (\is_resource($this->resource))
			\pg_close($this->resource);

		unset($this->platform);
		unset($this->builder);
		$this->resource = null;
	}

	public function isConnected()
	{
		return (\is_resource($this->resource) &&
			(\pg_connection_status($this->resource) ==
			PGSQL_CONNECTION_OK));
	}

	public function quoteStringValue($value)
	{
		return @\pg_escape_literal($this->resource, $value);
	}

	public function quoteIdentifier($identifier)
	{
		return @\pg_escape_identifier($this->resource, $identifier);
	}

	public function getPlatform()
	{
		if (!isset($this->platform))
		{
			$serverVersion = PostgreSQLPlatform::DEFAULT_VERSION;
			if ($this->isConnected())
			{
				$info = \pg_version($this->resource);
				if (\preg_match('/^[0-9]+(\.[0-9]+)*/', $info['server'],
					$m))
					$serverVersion = $m[0];
				$this->platform = new PostgreSQLPlatform($this,
					$serverVersion);
			}
		}

		return $this->platform;
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
		$result = \pg_prepare($this->resource, $identifier,
			\strval($statement));
		$status = \pg_result_status($result);
		if (pg_result_status($result) != PGSQL_COMMAND_OK)
			throw new ConnectionException($this,
				'Failed to prepare statement. ' .
				\pg_result_error($result));

		return new PostgreSQLPreparedStatement($identifier, $statement);
	}

	public function executeStatement($statement, $parameters = array())
	{
		$this->checkConnection();

		if (!($statement instanceof PostgreSQLPreparedStatement ||
			TypeDescription::hasStringRepresentation($statement)))
			throw new ConnectionException($this,
				'Invalide statement type. string or ' .
				PostgreSQLPreparedStatement::class . ' expected. Got ' .
				TypeDescription::getName($statement));

		$statementType = Statement::statementTypeFromData($statement);
		$pgResult = null;
		if (Container::count($parameters))
		{
			if ($statement instanceof PostgreSQLPreparedStatement)
			{
				$result = \pg_execute($this->resource,
					$statement->getPreparedStatementId(),
					self::getPostgreSQLParameterArray($statement,
						$parameters));
			}
			else
				$result = \pg_query_params($this->resource, $statement,
					self::getPostgreSQLParameterArray($statement,
						$parameters));
		}
		else
			$result = @\pg_query($this->resource, \strval($statement));

		if ($result === false)
			throw new ConnectionException($this,
				\pg_last_error($this->resource));

		if (!\is_resource($result))
			throw new \RuntimeException(__METHOD__ . ' Not implemented');

		$status = \pg_result_status($result);
		switch ($status)
		{
			case PGSQL_TUPLES_OK:
				$recordset = new PostgreSQLRecordset($result, $statement);
				$recordset->setDataUnserializer(
					PostgreSQLDataUnserializer::getInstance());
				return $recordset;
			break;

			case PGSQL_COMMAND_OK:
				if ($statementType & K::QUERY_FAMILY_ROWMODIFICATION)
					return new GenericRowModificationStatementResult(
						\pg_affected_rows($result));
				elseif ($statementType == K::QUERY_INSERT)
				{
					return new GenericInsertionStatementResult(
						$this->getLastInsertId());
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
		throw new ConnectionException($this,
			\pg_result_status($result, PGSQL_STATUS_STRING));
	}

	/**
	 *
	 * @deprecated
	 * @return \NoreSources\SemanticVersion
	 */
	public function getPostgreSQLVersion()
	{
		return $this->getPlatform()->getPlatformVersion();
	}

	/**
	 *
	 * @return resource The innert PostgreSQL connection resource
	 */
	public function getConnectionResource()
	{
		return $this->resource;
	}

	private static function getPostgreSQLParameterArray($statement,
		$parameters = array())
	{
		$a = [];
		$indexed = Container::isIndexed($parameters);
		if ($indexed) // Don not use names...
		{
			foreach ($parameters as $entry)
			{
				$a[] = $this->getPlatform()->literalize($entry);
			}

			return $a;
		}

		if ($statement instanceof ParameterDataProviderInterface)
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
				ParameterDataProviderInterface::class);

		return $a;
	}

	private function executeInstruction($sql)
	{
		$this->checkConnection();
		$result = \pg_query($this->resource, $sql);
		if ($result === false)
			throw new ConnectionException($this,
				\pg_last_error($this->resource));
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
		if (SemanticVersion::compareVersions(
			$this->getPostgreSQLVersion(), '7.2.0') < 0)
		{
			$id = \pg_last_oid();
		}
		elseif (SemanticVersion::compareVersions(
			$this->getPostgreSQLVersion(), '8.1.0') > 0)
		{
			$savePointKey = '_NS_PHP_SQL_LASTVAL_SAVEPOINT_';
			$transaction = @(pg_transaction_status($this->resource) !==
				PGSQL_TRANSACTION_IDLE);

			if ($transaction)
				@pg_query('SAVEPOINT ' . $savePointKey);
			$result = @\pg_query('SELECT LASTVAL()');
			if (\is_resource($result) &&
				pg_result_status($result, PGSQL_TUPLES_OK))
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
}