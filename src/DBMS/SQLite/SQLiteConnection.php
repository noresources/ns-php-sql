<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\Container\Container;
use NoreSources\Http\ParameterMapProviderInterface;
use NoreSources\SQL\DataDescription;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\TransactionInterface;
use NoreSources\SQL\DBMS\Configuration\ConfiguratorProviderTrait;
use NoreSources\SQL\DBMS\Explorer\StructureExplorerProviderInterface;
use NoreSources\SQL\DBMS\SQLite\SQLiteConstants as K;
use NoreSources\SQL\DBMS\Traits\PlatformProviderTrait;
use NoreSources\SQL\DBMS\Traits\TransactionStackTrait;
use NoreSources\SQL\Result\DefaultInsertionStatementResult;
use NoreSources\SQL\Result\DefaultRowModificationStatementResult;
use NoreSources\SQL\Syntax\Statement\ParameterData;
use NoreSources\SQL\Syntax\Statement\ParameterDataProviderInterface;
use NoreSources\SQL\Syntax\Statement\Statement;
use NoreSources\Type\TypeDescription;

/**
 * SQLite connection
 */
class SQLiteConnection implements ConnectionInterface,
	TransactionInterface, StructureExplorerProviderInterface
{
	use TransactionStackTrait;
	use PlatformProviderTrait;
	use ConfiguratorProviderTrait;

	const CONFIGURATION_FOREIGN_KEY_CONSTRAINTS_DEFAULT = 1;

	const CONFIGURATION_SUBMIT_TIMEOUT_DEFAULT = 5000;

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
	 * The default namespace name
	 *
	 * @var string
	 */
	const NAMESPACE_NAME_DEFAULT = 'main';

	/**
	 *
	 * @param array $settings
	 *        	Connection parameters
	 * @return boolean TRUE if the sqlite extension is available
	 */
	public static function acceptConnection($settings = array())
	{
		return \class_exists('\SQLite3');
	}

	/**
	 * Connect to DBMS
	 *
	 * @param \ArrayAccess $parameters
	 *        	SQLiteConnection parameters. Accepted keys are
	 *        	<ul>
	 *        	<li>CONNECTION_SOURCE (string|array):
	 *        	<ul>
	 *        	<li>If unspecified, use a in-memory storage</li>
	 *        	<li>If the parameter value is a string, the database will be loaded as the "main"
	 *        	database</li>
	 *        	<li>If the parameter value is an array, the elements key represents the namespace
	 *        	name,
	 *        	the values represents the database
	 *        	file name. If the key is not a string, the base file name is used as tableet
	 *        	name</li>
	 *        	</ul>
	 *        	CONNECTION_SOURCE value is a
	 *        	string</li>
	 *        	<li>CONNECTION_CREATE (bool): Create database file if it does not exists</li>
	 *        	<li>CONNECTION_READONLY (bool): Indicates the database is read only</li>
	 *        	<li>CONNECTION_ENCRYPTION_KEY (string): Database encryption key</li>
	 *        	</ul>
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
				return new SQLiteTransactionBlock($this, $name);
			});

		if ($this->connection instanceof \SQLite3)
			@$this->connection->close();

		$this->connection = null;

		$structure = Container::keyValue($parameters,
			K::CONNECTION_STRUCTURE);

		$pragmas = Container::keyValue($parameters,
			K::CONNECTION_SQLITE_PRAGMAS,
			[
				'foreign_keys' => self::CONFIGURATION_FOREIGN_KEY_CONSTRAINTS_DEFAULT,
				'busy_timeout' => self::CONFIGURATION_SUBMIT_TIMEOUT_DEFAULT
			]);

		$defaultNamespaceName = self::NAMESPACE_NAME_DEFAULT;

		$sources = Container::keyValue($parameters, K::CONNECTION_SOURCE,
			[
				$defaultNamespaceName => self::SOURCE_MEMORY
			]);

		$flags = 0;
		if (Container::keyValue($parameters, K::CONNECTION_READONLY,
			false))
		{
			$flags |= \SQLITE3_OPEN_READONLY;
		}
		else
		{
			$flags |= \SQLITE3_OPEN_READWRITE;
		}

		if (Container::keyValue($parameters, K::CONNECTION_CREATE, false))
		{
			if ($flags & \SQLITE3_OPEN_READONLY)
			{
				throw new SQLiteConnectionException($this,
					'Unable to set Auto-create and Read only flags at the same time');
			}

			$flags |= \SQLITE3_OPEN_CREATE;
		}

		if (\is_string($sources))
		{
			$sources = [
				$defaultNamespaceName => $sources
			];
		}

		$names = [];
		foreach ($sources as $name => $source)
		{
			$name = self::getNamespaceName($name, $source);

			if (\in_array($name, $names))
			{
				throw new SQLiteConnectionException($this,
					'Duplicated namespace name ' . $name);
			}

			$names[] = $name;

			$attach = false;
			$sql = "ATTACH DATABASE '" . \SQLite3::escapeString($source) .
				"'" . ' AS ' . '"' . \addslashes($name) . '"';

			if ($this->connection instanceof \SQLite3)
			{
				$attach = true;
			}
			else
			{
				$key = Container::keyValue($parameters,
					K::CONNECTION_ENCRYPTION_KEY, null);
				if ($name == self::NAMESPACE_NAME_DEFAULT)
				{
					$this->connection = new \SQLite3($source, $flags,
						$key);
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
					throw new SQLiteConnectionException($this,
						'Failed to attach database');
			}
		}

		foreach ($pragmas as $pragma => $value)
		{
			$result = @$this->connection->exec(
				'PRAGMA ' . $pragma . '=' . $value);
			if ($result === false)
				throw new SQLiteConnectionException($this,
					'Failed to set ' . $pragma . ' pragma');
		}
	}

	public function __destruct()
	{
		$this->endTransactions(false);
		if (!($this->connection instanceof \SQLite3))
			throw new SQLiteConnectionException($this, 'Not connected');
		@$this->connection->close();
		$this->connection = null;
	}

	public function __get($member)
	{
		if ($member == 'sqliteConnection')
			return $this->connection;

		throw new \InvalidArgumentException(
			$member . ' is not a member of ' .
			TypeDescription::getName($this));
	}

	public function isConnected()
	{
		return ($this->connection instanceof \SQLite3);
	}

	/**
	 *
	 * @return SQLitePlatform
	 */
	public function getPlatform()
	{
		if (!isset($this->platform))
		{
			$version = \Sqlite3::version();
			$parameters = \array_merge($this->platformParameters,
				[
					K::PLATFORM_VERSION_CURRENT => $version['versionString']
				]);
			$this->platform = new SQLitePlatform($parameters, $this);
		}

		return $this->platform;
	}

	/**
	 *
	 * @return \NoreSources\SQL\DBMS\SQLite\SQLiteStructureExplorer
	 */
	public function getStructureExplorer()
	{
		if (!isset($this->structureExplorer))
			$this->structureExplorer = new SQLiteStructureExplorer(
				$this);

		return $this->structureExplorer;
	}

	/**
	 *
	 * @param SQL\BuildContext|string $statement
	 * @return \NoreSources\SQL\DBMS\PreparedStatement
	 */
	public function prepareStatement($statement)
	{
		if (!($this->connection instanceof \SQLite3))
			throw new SQLiteConnectionException($this, 'Not connected');

		$detectParameters = !($statement instanceof ParameterDataProviderInterface);
		$sql = \strval($statement);
		$stmt = @$this->connection->prepare($sql);
		if (!($stmt instanceof \SQLite3Stmt))
			throw new SQLiteConnectionException($this,
				'Unable to create SQLite statement');

		$prepared = new SQLitePreparedStatement($stmt, $statement);

		/**
		 * Get parameter info if needed
		 *
		 * @see https://sqlite.org/lang_expr.html
		 * @see https://sqlite.org/lang_explain.html
		 */
		if ($detectParameters && $stmt->paramCount())
		{
			$map = $prepared->getParameters();
			$map->clear();
			$this->populateParameterData($map, $sql);
		}
		return $prepared;
	}

	public function executeStatement($statement, $parameters = array())
	{
		if (!($this->connection instanceof \SQLite3))
			throw new SQLiteConnectionException($this, 'Not connected');

		if (!($statement instanceof SQLitePreparedStatement ||
			TypeDescription::hasStringRepresentation($statement)))
			throw new SQLiteConnectionException($this,
				'Invalid statement type ' .
				TypeDescription::getName($statement) .
				'. Expect PreparedStatement or stringifiable');
		;

		$result = null;
		$statementType = Statement::statementTypeFromData($statement);

		if (Container::count($parameters))
		{
			/**
			 *
			 * @var \SQLite3Stmt $stmt
			 */
			$stmt = null;

			if ($statement instanceof SQLitePreparedStatement)
			{
				$stmt = $statement->getSQLite3Stmt();
				$stmt->clear();
				$stmt->reset();
			}
			else
				$stmt = @$this->connection->prepare($statement);

			if ($stmt->paramCount())
			{
				/**
				 *
				 * @var ParameterMapProviderInterface $map
				 */
				$map = null;

				if ($statement instanceof ParameterDataProviderInterface)
					$map = $statement->getParameters();
				else
					$this->populateParameterData(
						($map = new ParameterData()), $statement);

				foreach ($parameters as $key => $entry)
				{
					if (!$map->has($key))
						continue;

					$parameterData = $map->get($key);
					$dbmsName = $parameterData[ParameterData::DBMSNAME];

					$dataType = Container::keyValue($parameterData,
						ParameterData::DATATYPE,
						DataDescription::getInstance()->getDataType(
							$entry));
					$value = $this->getPlatform()->literalize($entry,
						$dataType);

					/**
					 * SQLite does not have type for DateTIme etc.
					 * but Date/Time functions
					 * expects a strict datetime format.
					 *
					 * Workaround: format DateTIme to string with the correct format before
					 */

					if ($dataType & K::DATATYPE_TIMESTAMP)
					{
						if ($value instanceof \DateTimeInterface)
							$value = $value->format(
								$this->getPlatform()
									->getTimestampTypeStringFormat(
									$dataType));
					}

					$type = self::sqliteDataTypeFromDataType($dataType);
					$bindResult = $stmt->bindValue($dbmsName, $value,
						$type);
					if (!$bindResult)
						throw new SQLiteConnectionException($this,
							'Failed to bind "' . $key . '" (' . $dbmsName .
							')');
				}
			}

			$result = @$stmt->execute();
		}
		else
		{

			$rowExpected = ($statementType == K::QUERY_SELECT ||
				$statementType == 0);

			if ($rowExpected)
				$result = @$this->connection->query(strval($statement));
			else
				$result = @$this->connection->exec(strval($statement));
		}

		if ($result === false)
			throw new SQLiteConnectionException($this,
				'Failed to execute statement of type ' .
				K::statementTypeName($statementType));

		if ($statementType & K::QUERY_FAMILY_ROWMODIFICATION)
		{
			return new DefaultRowModificationStatementResult(
				@$this->connection->changes());
		}
		elseif ($statementType == K::QUERY_INSERT)
		{
			return new DefaultInsertionStatementResult(
				@$this->connection->lastInsertRowID());
		}
		elseif ($statementType == K::QUERY_SELECT ||
			($result instanceof \SQLite3Result &&
			($result->numColumns() || ($result->columnType(0) !== false))))
		{
			return new SQLiteRecordset($result, $this, $statement);
		}
		else
			return (($result instanceof \SQLite3Result) || $result);

		throw new SQLiteConnectionException($this,
			'Failed to execute statement of type ' .
			K::statementTypeName($statementType));
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
			case K::DATATYPE_REAL:
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
				return K::DATATYPE_REAL;
			case \SQLITE3_INTEGER:
				return K::DATATYPE_INTEGER;
			case \SQLITE3_NULL:
				return K::DATATYPE_NULL;
		}

		return K::DATATYPE_STRING;
	}

	public function getSQLite()
	{
		return $this->connection;
	}

	private function populateParameterData(ParameterData $map,
		$statement)
	{
		$explained = $this->connection->query(
			'EXPLAIN ' . \strval($statement));
		$index = 0;
		while ($row = $explained->fetchArray())
		{
			if ($row['opcode'] != 'Variable')
				continue;
			$dbmsName = $row['p4'];
			$prefix = \substr($dbmsName, 0, 1);
			$key = \substr($dbmsName, 1);
			if (empty($key))
			{
				$key = $index;
				$dbmsName = '?';
			}

			$map->setParameter($index, $key, $dbmsName);
			$index++;
		}
	}

	/**
	 * Get a namespace name for the given database source
	 *
	 * @param mixed $name
	 *        	User-defined name
	 * @param string $source
	 *        	Database source
	 * @return string
	 */
	private static function getNamespaceName($name, $source)
	{
		if (is_string($name) && strlen($name))
			return $name;

		if ($source == self::SOURCE_MEMORY ||
			$source == self::SOURCE_TEMPORARY)
		{
			return self::NAMESPACE_NAME_DEFAULT;
		}

		return pathinfo($source, PATHINFO_FILENAME);
	}

	/**
	 *
	 * @var \NoreSources\SQL\Syntax\Statement\StatementFactoryInterface
	 */
	private $statementFactory;

	/**
	 * DBMS connection
	 *
	 * @var \SQLite3
	 */
	private $connection;

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