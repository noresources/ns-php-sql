<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\PDO;

use NoreSources\Container;
use NoreSources\SemanticVersion;
use NoreSources\TypeConversion;
use NoreSources\TypeDescription;
use NoreSources\SQL\DBMS\BinaryDataSerializerInterface;
use NoreSources\SQL\DBMS\ConnectionException;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\StringSerializerInterface;
use NoreSources\SQL\DBMS\TransactionInterface;
use NoreSources\SQL\DBMS\Configuration\ConfiguratorProviderInterface;
use NoreSources\SQL\DBMS\Configuration\ConfiguratorProviderTrait;
use NoreSources\SQL\DBMS\Explorer\StructureExplorerProviderInterface;
use NoreSources\SQL\DBMS\MySQL\MySQLPlatform;
use NoreSources\SQL\DBMS\MySQL\MySQLStructureExplorer;
use NoreSources\SQL\DBMS\PDO\PDOConstants as K;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLPlatform;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLStructureExplorer;
use NoreSources\SQL\DBMS\Reference\ReferencePlatform;
use NoreSources\SQL\DBMS\Reference\ReferenceTransactionBlock;
use NoreSources\SQL\DBMS\SQLite\SQLitePlatform;
use NoreSources\SQL\DBMS\SQLite\SQLiteStructureExplorer;
use NoreSources\SQL\DBMS\Traits\PlatformProviderTrait;
use NoreSources\SQL\DBMS\Traits\TransactionStackTrait;
use NoreSources\SQL\Result\DefaultInsertionStatementResult;
use NoreSources\SQL\Result\DefaultRowModificationStatementResult;
use NoreSources\SQL\Syntax\Evaluator;
use NoreSources\SQL\Syntax\Statement\ParameterData;
use NoreSources\SQL\Syntax\Statement\ParameterDataProviderInterface;
use NoreSources\SQL\Syntax\Statement\Statement;
use PDO;

/**
 * PDO connection
 */
class PDOConnection implements ConnectionInterface, TransactionInterface,
	StringSerializerInterface, BinaryDataSerializerInterface,
	StructureExplorerProviderInterface, ConfiguratorProviderInterface
{
	use TransactionStackTrait;
	use PlatformProviderTrait;
	use ConfiguratorProviderTrait;

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
		$a = Container::createArray($array);
		$prefix = \array_shift($a);

		return $prefix . ':' .
			Container::implode($a, ':',
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
	 *        	Connection parameters
	 * @return boolean TRUE if the PDO extension is available and
	 *         (if provided) the requested driver is available
	 */
	public static function acceptConnection($parameters = array())
	{
		if (!\class_exists('\PDO'))
			return false;

		if ($parameters === null)
			return true;

		$dsn = Container::keyValue($parameters, K::CONNECTION_SOURCE,
			null);

		if ($dsn === null)
			return true;

		if (Container::isArray($dsn))
			$dsn = self::buildDSN($dsn);
		if (!\is_string($dsn))
			return false;

		$driver = Container::firstValue(explode(':', $dsn));
		$drivers = \PDO::getAvailableDrivers();

		return \in_array($driver, $drivers);

		return true;
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
		$this->connection = null;
		$platformParameters = [
			K::CONNECTION_STRUCTURE_FILENAME_FACTORY
		];
		$this->platformParameters = Container::filter($parameters,
			function ($k, $v) use ($platformParameters) {
				return \in_array($k, $platformParameters);
			});

		$this->setTransactionBlockFactory(
			function ($depth, $name) {
				return new ReferenceTransactionBlock($this, $name);
			});

		if ($this->connection instanceof \PDO)
			$this->connection->close();

		$structure = Container::keyValue($parameters,
			K::CONNECTION_STRUCTURE);

		$dsn = Container::keyValue($parameters, K::CONNECTION_SOURCE,
			null);
		$user = Container::keyValue($parameters, K::CONNECTION_USER,
			null);
		$password = Container::keyValue($parameters,
			K::CONNECTION_PASSWORD, null);
		$options = Container::keyValue($parameters,
			K::CONNECTION_OPTIONS, null);

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
			$this->connection = new \PDO($dsn, $user, $password,
				$options);
		}
		catch (\PDOException $e)
		{
			throw new ConnectionException($this,
				$e->getMessage() . ' ' . $dsn, $e->getCode());
		}

		try
		{
			$this->driverName = $this->getPDOAttribute(
				\PDO::ATTR_DRIVER_NAME);
		}
		catch (\Exception $e)
		{}
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
			$status = $this->connection->getAttribute(
				\PDO::ATTR_CONNECTION_STATUS);
			if (\is_string($status))
			{
				if ((\stristr($status, 'error') !== false) ||
					(\stristr($status, 'error') !== false))
					$status = false;
				else
					$status = true;
			}

			$status = TypeConversion::toBoolean($status);
		}
		catch (\Exception $e)
		{}

		return $status;
	}

	public function quoteStringValue($value)
	{
		return $this->connection->quote($value, \PDO::PARAM_STR);
	}

	public function quoteBinaryData($value)
	{
		return $this->connection->quote($value, \PDO::PARAM_LOB);
	}

	public function quoteIdentifier($identifier)
	{
		$c = '"';
		try
		{
			switch ($this->driverName)
			{
				case self::DRIVER_MYSQL:
					$c = '`';
				break;
			}
		}
		catch (\Exception $e)
		{}

		return $c . \str_replace($c, $c . $c, $identifier) . $c;
	}

	public function getStructureExplorer()
	{
		if (!isset($this->structureExplorer))
		{
			$className = Container::keyValue(
				[
					self::DRIVER_POSTGRESQL => PostgreSQLStructureExplorer::class,
					self::DRIVER_SQLITE => SQLiteStructureExplorer::class,
					self::DRIVER_MYSQL => MySQLStructureExplorer::class
				], $this->driverName, null);

			if (!$className)
				throw new ConnectionException(
					'No structure explorer for ' . $this->driverName);

			$cls = new \ReflectionClass($className);
			$this->structureExplorer = $cls->newInstanceArgs([
				$this
			]);
		}

		return $this->structureExplorer;
	}

	public function getPlatform()
	{
		if (!isset($this->platform))
		{
			$version = null;
			try
			{
				$serverVersion = $this->getPDOAttribute(
					\PDO::ATTR_SERVER_VERSION);
				$clientVersion = $this->getPDOAttribute(
					\PDO::ATTR_CLIENT_VERSION);
				$pattern = '/^[0-9]+(\.[0-9]+)*/';
				if (\preg_match($pattern, $serverVersion, $m))
					$serverVersion = $m[0];
				if (\preg_match($pattern, $clientVersion, $m))
					$clientVersion = $m[0];

				$delta = SemanticVersion::compareVersions(
					$serverVersion, $clientVersion);

				$version = ($delta < 0) ? $serverVersion : $clientVersion;
			}
			catch (\Exception $e)
			{}

			$platformClassName = Container::keyValue(
				[
					self::DRIVER_POSTGRESQL => PostgreSQLPlatform::class,
					self::DRIVER_SQLITE => SQLitePlatform::class,
					self::DRIVER_MYSQL => MySQLPlatform::class
				], $this->driverName, ReferencePlatform::class);

			$platformClass = new \ReflectionClass($platformClassName);

			if ($version === null)
				if ($platformClass->hasConstant('DEFAULT_VERSION'))
					$version = $platformClass->getConstant(
						'DEFAULT_VERSION');
				else
					$version = '0.0.0';

			$platformParameters = \array_merge(
				$this->platformParameters,
				[
					K::PLATFORM_VERSION_CURRENT => $version
				]);

			$basePlatform = $platformClass->newInstance(
				$platformParameters, $this);
			$this->platform = new PDOPlatform($this, $basePlatform);
		}

		return $this->platform;
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
			throw new ConnectionException($this,
				'Failed to prepare statement. ' . $message);
		}

		return new PDOPreparedStatement($pdo, $statement);
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\DBMS\ConnectionInterface::executeStatement()
	 */
	public function executeStatement($statement, $parameters = array())
	{
		if (!($this->connection instanceof \PDO))
			throw new ConnectionException($this, 'Not connected');

		/**
		 *
		 * @var \PDOStatement $pdo
		 */
		$pdo = null;

		if ($statement instanceof PDOPreparedStatement)
		{
			$pdo = $this->connection->prepare(
				$statement->getPDOStatement()->queryString);
		}
		elseif (TypeDescription::hasStringRepresentation($statement))
		{
			$pdo = $this->connection->prepare(
				TypeConversion::toString($statement));
		}
		else
			throw new \InvalidArgumentException(
				'Invalid type ' . TypeDescription::getName($statement) .
				' for statement argument. string or ' .
				PDOPreparedStatementInterface::class . ' expected');

		if ($pdo === false)
			throw new ConnectionException($this,
				'Failed to prepare statement');

		if (Container::count($parameters))
		{
			$platform = $this->getPlatform();
			if ($statement instanceof ParameterDataProviderInterface)
			{
				$map = $statement->getParameters();
				$indexedValues = Container::isIndexed($parameters);
				$useNamedParameter = $platform->queryFeature(
					K::FEATURE_NAMED_PARAMETERS, false);

				// Bind everything to NULL by default
				if ($useNamedParameter)
				{
					foreach ($map->getKeyIterator() as $parameter)
					{
						$pdo->bindValue(
							$parameter[ParameterData::DBMSNAME], NULL,
							\PDO::PARAM_NULL);
					}
				}
				else
				{
					$c = $map->count();
					for ($i = 0; $i < $c; $i++)
					{

						$pdo->bindValue($i + 1, NULL, \PDO::PARAM_NULL);
					}
				}

				foreach ($parameters as $key => $entry)
				{
					$value = $platform->literalize($entry);
					$pdoType = self::getPDOTypeFromDataType(
						Evaluator::getDataType($entry));

					$parameterData = $map->get($key);

					if ($indexedValues)
					{
						if ($useNamedParameter)
							$pdoParameter = $parameterData[ParameterData::DBMSNAME];
						else
							$pdoParameter = $key + 1;

						$pdo->bindValue($pdoParameter, $value, $pdoType);
					}
					else
					{
						if ($useNamedParameter)
						{
							$pdo->bindValue(
								$parameterData[ParameterData::DBMSNAME],
								$value, $pdoType);
						}
						else
						{
							$positions = $parameterData[ParameterData::POSITIONS];
							foreach ($positions as $index)
								$pdo->bindValue($index + 1, $value,
									$pdoType);
						}
					}
				}
			}
			elseif (Container::isIndexed($parameters))
			{
				foreach ($parameters as $index => $entry)
				{
					$pdo->bindValue($index + 1,
						$platform->literalize($entry),
						self::getPDOTypeFromDataType(
							Evaluator::getDataType($entry)));
				}
			}
			else // Key-value
			{
				foreach ($parameters as $key => $entry)
				{
					$pdo->bindValue($platform->getParameter($key),
						$platform->literalize($entry),
						self::getPDOTypeFromDataType(
							Evaluator::getDataType($entry)));
				}
			}
		}

		$result = $pdo->execute();
		if ($result === false)
		{
			$error = $pdo->errorInfo();
			$message = self::getErrorMessage($error);
			throw new ConnectionException($this,
				'Execution error: ' . $message);
		}

		$result = true;
		$type = Statement::statementTypeFromData($statement);

		if ($type == K::QUERY_SELECT || ($pdo->columnCount()))
			$result = (new PDORecordset($pdo, $statement));
		elseif ($type == K::QUERY_INSERT)
			$result = new DefaultInsertionStatementResult(
				$this->connection->lastInsertId());
		elseif ($type & K::QUERY_FAMILY_ROWMODIFICATION)
			$result = new DefaultRowModificationStatementResult(
				$pdo->rowCount());

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

	/**
	 *
	 * @param integer $attribute
	 * @throws ConnectionException
	 * @return mixed
	 */
	public function getPDOAttribute($attribute)
	{
		if (!($this->connection instanceof \PDO))
			throw new ConnectionException($this, 'Not connected');

		return $this->connection->getAttribute($attribute);
	}

	public static function getPDOTypeFromDataType($dataType)
	{
		if ($dataType == K::DATATYPE_NULL)
			return \PDO::PARAM_NULL;
		if ($dataType & K::DATATYPE_BOOLEAN)
			return PDO::PARAM_BOOL;
		if ($dataType & K::DATATYPE_BINARY)
			return PDO::PARAM_LOB;
		elseif (($dataType & K::DATATYPE_NUMBER) == K::DATATYPE_INTEGER)
			return PDO::PARAM_INT;

		return PDO::PARAM_STR;
	}

	/**
	 *
	 * @param integer $pdoType
	 * @return string
	 */
	public static function getDataTypeFromPDOType($pdoType)
	{
		switch ($pdoType)
		{
			case \PDO::PARAM_LOB:
				return K::DATATYPE_BINARY;
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
	 * @return PDO
	 */
	public function getConnectionObject()
	{
		return $this->connection;
	}

	/**
	 *
	 * @var \NoreSources\SQL\Syntax\Statement\StatementFactoryInterface
	 */
	private $statementFactory;

	/**
	 * DBMS connection
	 *
	 * @var \PDO
	 */
	private $connection;

	/**
	 *
	 * @var string
	 */
	private $driverName;

	/**
	 *
	 * @var array
	 */
	private $platformParameters;

	/**
	 *
	 * @var SQLiteStructureExplorer
	 */
	private $structureExplorer;
}