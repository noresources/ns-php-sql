<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\Container;
use NoreSources\TypeDescription;
use NoreSources\SQL\ParameterValue;
use NoreSources\SQL\DBMS\ConnectionHelper;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\PlatformProviderTrait;
use NoreSources\SQL\DBMS\TransactionInterface;
use NoreSources\SQL\DBMS\TransactionStackTrait;
use NoreSources\SQL\DBMS\SQLite\SQLiteConstants as K;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Result\GenericInsertionStatementResult;
use NoreSources\SQL\Result\GenericRowModificationStatementResult;
use NoreSources\SQL\Statement\ParameterData;
use NoreSources\SQL\Statement\ParameterDataProviderInterface;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureProviderTrait;

/**
 * SQLite connection
 */
class SQLiteConnection implements ConnectionInterface,
	TransactionInterface
{
	use StructureProviderTrait;
	use TransactionStackTrait;
	use PlatformProviderTrait;

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

		$this->setTransactionBlockFactory(
			function ($depth, $name) {
				return new SQLiteTransactionBlock($this, $name);
			});

		if ($this->connection instanceof \SQLite3)
			$this->connection->close();

		$this->connection = null;

		$structure = Container::keyValue($parameters,
			K::CONNECTION_STRUCTURE);
		if ($structure instanceof StructureElementInterface)
			$this->setStructure($structure);

		$pragmas = Container::keyValue($parameters,
			K::CONNECTION_SQLITE_PRAGMAS,
			[
				'foreign_keys' => 1,
				'busy_timeout' => 5000
			]);

		$defaultNamespaceName = self::NAMESPACE_NAME_DEFAULT;
		$structure = $this->getStructure();
		if ($structure instanceof NamespaceStructure)
		{
			$defaultNamespaceName = $structure->getName();
		}
		elseif ($structure instanceof DatasourceStructure &&
			$structure->count() == 1)
		{
			list ($name, $namespace) = Container::first(
				$structure->getChildElements());
			$defaultNamespaceName = $name;
		}

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

			$settings = [];
			foreach ([
				K::CONNECTION_DATABASE_FILE_PROVIDER
			] as $setting)
			{
				if (Container::keyExists($parameters, $setting))
					$settings[$setting] = $parameters[$setting];
			}

			$this->getStatementBuilder()->setSQLiteSettings($settings);
		}

		foreach ($pragmas as $pragma => $value)
		{
			$result = $this->connection->exec(
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
		$this->connection->close();
		$this->connection = null;
	}

	public function __get($member)
	{
		if ($member == 'sqliteConnection')
			return $this->connection;

		return parent::__get($key);
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
			$this->platform = new SQLitePlatform(
				$version['versionString']);
		}

		return $this->platform;
	}

	public function getStatementBuilder()
	{
		if (!isset($this->builder))
			$this->builder = new SQLiteStatementBuilder(
				$this->getPlatform());

		return $this->builder;
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

		$stmt = $this->connection->prepare($statement);
		if (!($stmt instanceof \SQLite3Stmt))
			throw new SQLiteConnectionException($this,
				'Unable to create SQLite statement');

		return new SQLitePreparedStatement($stmt, $statement);
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
			$stmt = null;
			if ($statement instanceof SQLitePreparedStatement)
			{
				$stmt = $statement->getSQLite3Stmt();
				$stmt->clear();
				$stmt->reset();
			}
			else
				$stmt = $this->connection->prepare($statement);

			foreach ($parameters as $key => $entry)
			{
				$dbmsName = $key;
				if ($statement instanceof ParameterDataProviderInterface)
					$dbmsName = $statement->getParameters()->get($key)[ParameterData::DBMSNAME];
				else
					$dbmsName = $this->getStatementBuilder()->getParameter(
						$key, null);

				$value = ConnectionHelper::serializeParameterValue(
					$this, $entry);
				$type = ($entry instanceof ParameterValue) ? $entry->type : K::DATATYPE_UNDEFINED;

				if ($type == K::DATATYPE_UNDEFINED)
					$type = Literal::dataTypeFromValue($value);

				/**
				 * SQLite does not have type for DateTIme etc.
				 * but Date/Time functions
				 * expects a strict datetime format.
				 *
				 * Workaround: format DateTIme to string with the correct format before
				 */

				if ($type & K::DATATYPE_TIMESTAMP)
				{
					if ($value instanceof \DateTimeInterface)
						$value = $value->format(
							$this->getStatementBuilder()
								->getPlatform()
								->getTimestampTypeStringFormat($type));
				}

				$type = self::sqliteDataTypeFromDataType($type);
				$bindResult = $stmt->bindValue($dbmsName, $value, $type);
				if (!$bindResult)
					throw new SQLiteConnectionException($this,
						'Failed to bind "' . $key . '" (' . $dbmsName .
						')');
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
			throw new SQLiteConnectionException($this,
				'Failed to execute statement of type ' .
				K::statementTypeName($statementType));

		if ($statementType & K::QUERY_FAMILY_ROWMODIFICATION)
		{
			if (($result instanceof \SQLite3Result) || $result)
			{
				return new GenericRowModificationStatementResult(
					$this->connection->changes());
			}
		}
		elseif ($statementType == K::QUERY_INSERT)
		{
			if (($result instanceof \SQLite3Result) || $result)
			{
				return new GenericInsertionStatementResult(
					$this->connection->lastInsertRowID());
			}
		}
		elseif ($statementType == K::QUERY_SELECT)
		{
			if ($result instanceof \SQLite3Result)
				return new SQLiteRecordset($result, $statement);
			else
				throw new SQLiteConnectionException($this,
					'Invalid execution result');
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

	public function getSQLite()
	{
		return $this->connection;
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

		return pathinfo($source, 'filename');
	}

	/**
	 *
	 * @var SQLiteStatementBuilder
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
	 * @var \SQLite3
	 */
	private $connection;
}