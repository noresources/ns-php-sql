<?php

/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources as ns;
require_once (__DIR__ . '/../base.php');

/**
 *
 * @todo Update to use SQL*Structure
 */
class SQLiteTableManipulator extends TableManipulator
{

	public function __construct(ITableProvider $a_oProvider = null)
	{
		parent::__construct($a_oProvider);
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see NoreSources.TableManipulator::create()
	 */
	public function create(TableStructure $a_structure)
	{
		$t = new Table($this->m_provider, $a_structure->getName());
		$strQuery = 'CREATE TABLE ' . $t->expressionString() . ' (';

		$first = true;
		foreach ($a_structure as $name => $column)
		{
			if (!$first)
			{
				$strQuery .= ",\n";
			}
			$first = false;

			$strQuery .= $this->m_datasource->encloseElement($column->getName());
			$type = $column->getProperty(kStructureDatatype);
			$auto = $column->getProperty(kStructureAutoincrement);
			$acceptNull = $column->getProperty(kStructureAcceptNull);
			if (!is_null($type))
			{
				$strQuery .= ' ' . $this->m_datasource->getDefaultTypeName($type);
			}

			if (!$acceptNull || $auto)
			{
				$strQuery .= ' NOT NULL';
			}

			if ($auto)
			{
				$strQuery .= ' PRIMARY KEY AUTOINCREMENT';
			}
		}

		$strQuery .= ')';

		$q = new FormattedQuery($this->m_provider->datasource, $strQuery);

		return $q->execute();
	}
}

class SQLiteTableSet extends TableSet
{

	public function __construct(SQLiteDatasource $datasource, $name)
	{
		parent::__construct($datasource, $name, $datasource->getStructure()->offsetGet($name));
	}
}

/**
 * SQLite datasource implementation
 */
class SQLiteDatasource extends Datasource implements ITransactionBlock, ITableProvider
{

	// construction - destruction
	const kDatabaseNameMemory = ':memory:';

	const kDatabaseNameDefault = 'main';

	/**
	 * Legacy implementation (sqlite_*) functions
	 *
	 * @var integer
	 */
	const kImplementationLegacy = 1;

	/**
	 * SQLite3 object implementation
	 *
	 * @var integer
	 */
	const kImplementationSQLite3 = 2;

	public function __construct()
	{
		parent::__construct();
		$this->setDatasourceString(self::kStringClassNameTableManipulator,
			__NAMESPACE__ . '\\SQLiteTableManipulator');
		$this->setDatasourceString(self::kStringImplementationTypeKey, basename(__DIR__));
		$n = new FormattedData("'now'");
		$this->setNowExpression($n);

		if (extension_loaded('sqlite3'))
		{
			$this->m_implementation = self::kImplementationSQLite3;
		}
		elseif (extension_loaded('sqlite'))
		{
			$this->m_implementation = self::kImplementationLegacy;
		}
		else
		{
			$this->m_implementation = null;
		}

		$this->m_databaseName = null;
	}

	public function __destruct()
	{
		parent::__destruct();
	}

	// ITransactionBlock implementation
	function startTransaction()
	{
		$oQuery = new FormattedQuery($this, 'BEGIN TRANSACTION;');
		$oQuery->execute();
	}

	function commitTransaction()
	{
		$oQuery = new FormattedQuery($this, 'COMMIT TRANSACTION;');
		$oQuery->execute();
	}

	function rollbackTransaction()
	{
		$oQuery = new FormattedQuery($this, 'ROLLBACK TRANSACTION;');
		$oQuery->execute();
	}

	public function getDatasource()
	{
		return $this;
	}

	/**
	 * Get a table from the main database
	 *
	 * @see \NoreSources\SQL\ITableProvider::getTable()
	 */
	public function getTable($a_name, $a_strAlias = null, $a_className = null,
		$useAliasAsName = false)
	{
		$subStructure = null;
		if ($this->structure)
		{
			$db = $this->structure->offsetGet($this->m_databaseName);
			if ($db)
			{
				$subStructure = $db->offsetGet($a_name);
			}
			else // simplified structure version
			{
				$subStructure = $this->structure->offsetGet($a_name);
			}
		}

		$res = tableProviderGenericTableObjectMethod($this, $subStructure, $a_name, $a_strAlias,
			$a_className, $useAliasAsName);
		return $res;
	}

	/**
	 * List of main database tables
	 *
	 * @see \NoreSources\SQL\ITableProvider::tableIterator()
	 *
	 * @return \Iterator
	 */
	public function tableIterator()
	{
		if ($this->structure)
		{
			$s = $this->structure->offsetGet($this->m_databaseName);
			if ($s)
			{
				return $s;
			}
		}

		return null;
	}

	/**
	 * Indicates if the table exists
	 *
	 * @see NoreSources.ITableProvider::tableExists()
	 */
	public function tableExists($a_name, $a_mode = kObjectQuerySchema)
	{
		$result = true;
		if ($a_mode & kObjectQuerySchema)
		{
			$result = false;
			if ($this->structure)
			{
				$db = ($this->structure->offsetGet($this->m_databaseName));
				if ($db)
				{
					$result = $db->offsetExists($a_name);
				}
			}

			if (!$result)
				return false;
		}

		if ($a_mode & kObjectQueryDatasource)
		{
			$a = $this->getTableSetStructure($this, false);
			$result = ($result &&
				(($a instanceof TableSetStructure) && $a->offsetExists($a_name) &&
				($a[$a_name] instanceof TableStructure)));
		}

		return $result;
	}

	public function getDefaultTableSet()
	{
		return self::kDatabaseNameDefault;
	}

	public function getActiveTableSet()
	{
		return $this->m_databaseName;
	}

	// ITableSetProvider
	public function setActiveTableSet($name)
	{
		if ($this->m_databaseName != $name)
		{
			throw new \Exception('Set active TableSet is not allowed');
		}

		return true;
	}

	// Datasource implementation

	/**
	 * Connect to a SQLite source
	 *
	 * @param array $a_aParameters
	 *        	Datasource parameters
	 * @return bool
	 */
	public function connect($a_aParameters)
	{
		if ($this->resource)
		{
			$this->disconnect();
		}

		// Min requirements
		if (!ns\ArrayUtil::keyExists($a_aParameters, kConnectionParameterHostname) &&
			!ns\ArrayUtil::keyExists($a_aParameters, kConnectionParameterFilename))
		{
			throw new \Exception(
				'Parameters are missing. "' . kConnectionParameterHostname . '" or "' .
				kConnectionParameterFilename . '"] must be provided.');
		}

		if (ns\ArrayUtil::keyValue($a_aParameters, kConnectionParameterPersistent, false))
		{
			$this->setDatasourceFlags($this->flags | kConnectionPersistent);
		}

		$this->m_databaseName = ns\ArrayUtil::keyValue($a_aParameters,
			kConnectionParameterDatabasename, self::kDatabaseNameDefault);
		$fileName = ns\ArrayUtil::keyValue($a_aParameters, kConnectionParameterFilename,
			ns\ArrayUtil::keyValue($a_aParameters, kConnectionParameterHostname, null));

		if ($fileName != self::kDatabaseNameMemory)
		{
			if (\file_exists($fileName))
			{
				$fileName = realpath($fileName);
			}

			if (!(\file_exists($fileName) ||
				ns\ArrayUtil::keyValue($a_aParameters, kConnectionParameterCreate, false)))
			{
				throw new \Exception('Invalid file "' . $fileName . '"');
			}
		}

		$mainDatabase = $fileName;
		if ($this->m_databaseName != self::kDatabaseNameDefault)
		{
			$mainDatabase = self::kDatabaseNameMemory;
			if (!file_exists($fileName) &&
				ns\ArrayUtil::keyValue($a_aParameters, kConnectionParameterCreate, false))
			{
				// Force file creation here
				$p = $a_aParameters;
				$p[kConnectionParameterDatabasename] = self::kDatabaseNameDefault;
				$c = new SQLiteDatasource($this->getStructure());
				$c->connect($p);
				$c->disconnect();
			}
		}

		$errorMessage = '';
		if ($this->m_implementation == self::kImplementationSQLite3)
		{
			if ($this->flags & kConnectionPersistent)
			{
				ns\Reporter::warning($this,
					__METHOD__ . ': Persistent connection is not supported by SQLite3 implementation');
			}

			$flags = 0;
			$ekey = '';

			if (ns\ArrayUtil::keyValue($a_aParameters, kConnectionParameterCreate, false))
			{
				$flags |= SQLITE3_OPEN_CREATE;
			}

			if (ns\ArrayUtil::keyValue($a_aParameters, kConnectionParameterReadOnly, false))
			{
				$flags |= SQLITE3_OPEN_READONLY;
			}
			else
			{
				$flags |= SQLITE3_OPEN_READWRITE;
			}

			try
			{
				$this->m_datasourceResource = new \SQLite3($mainDatabase, $flags, $ekey);
			}
			catch (\Exception $e)
			{
				throw new \Exception(
					'Unable to open database "' . $fileName . '" :' . $e->getMessage());
			}
		}
		elseif ($this->m_implementation == self::kImplementationLegacy)
		{
			$mode = 0666;
			if (ns\ArrayUtil::keyValue($a_aParameters, kConnectionParameterReadOnly, false))
			{
				$mode = 0444;
			}

			$connectionFunction = 'sqlite_open';
			if ($this->flags & kConnectionPersistent)
			{
				$connectionFunction = 'sqlite_popen';
			}

			$this->m_datasourceResource = $connectionFunction($mainDatabase, $mode, $errorMessage);
		}
		else
		{
			$errorMessage = 'No implementation found';
		}

		if (strlen($errorMessage))
		{
			throw new \Exception(
				'Unable to connect to database ' . basename($fileName) . ': ' . $errorMessage);
		}

		$fkState = ns\ArrayUtil::keyValue($a_aParameters, kConnectionParameterForeignKeySupport,
			true) ? 'on' : 'off';
		$this->resultlessQuery('pragma foreign_keys=' . $fkState);

		// Use attach rather than open
		if ($this->m_databaseName != self::kDatabaseNameDefault)
		{
			$v = new StringData($this);
			$v->import($fileName);
			$str = 'ATTACH DATABASE ' . $v->expressionString() . ' AS ' .
				$this->encloseElement($this->m_databaseName) . ';';
			$res = $this->executeQuery($str);
			return ($res != false);
		}

		return true;
	}

	protected function disconnect()
	{
		if ($this->m_implementation == self::kImplementationSQLite3)
		{
			if (!($this->resource instanceof \SQLite3))
			{
				return false;
			}

			$this->resource->close();
		}
		elseif ($this->m_implementation == self::kImplementationLegacy)
		{
			if (!$this->resource)
			{
				return false;
			}
			sqlite_close($this->resource);
		}
		return true;
	}

	public function serializeStringData($stringData)
	{
		if (method_exists('\\SQLite3', 'escapeString'))
		{
			$$stringData = \SQLite3::escapeString($stringData);
		}
		elseif (function_exists('sqlite_escape_string'))
		{
			$$stringData = sqlite_escape_string($stringData);
		}

		return $$stringData;
	}

	public function serializeBinaryData($data)
	{
		return "X'" . bin2hex($data) . "'";
	}

	/**
	 * SQLite automatically unserialize data on SELECT
	 */
	public function unserializeBinaryData($data)
	{
		return $data;
	}

	public function executeQuery($a_strQuery)
	{
		$errorMessage = '';
		$result = null;
		if ($this->m_implementation == self::kImplementationSQLite3)
		{
			if (!($this->resource instanceof \SQLite3))
			{
				throw new \Exception('No Datasource');
			}

			$result = @$this->resource->query($a_strQuery);
		}
		elseif ($this->m_implementation == self::kImplementationLegacy)
		{
			if (!$this->resource)
			{
				throw new \Exception('No Datasource');
			}

			$result = sqlite_query($this->resource, $a_strQuery, SQLITE_BOTH, $errorMessage);
		}

		if (!$result)
		{
			if ($this->m_implementation == self::kImplementationSQLite3)
			{
				$errorMessage = $this->resource->lastErrorMsg();
			}
			elseif ($this->m_implementation == self::kImplementationLegacy)
			{
				$errorMessage = sqlite_error_string(sqlite_last_error($this->resource));
			}

			throw new \Exception('Query error: ' . $a_strQuery . ' / ' . $errorMessage);
		}

		return $result;
	}

	/**
	 *
	 * @param QueryResult $a_queryResult
	 * @return integer
	 */
	public function getLastInsertId(QueryResult $a_queryResult = null)
	{
		if ($this->m_implementation == self::kImplementationSQLite3)
		{
			return $this->resource->lastInsertRowID();
		}
		elseif ($this->m_implementation == self::kImplementationLegacy)
		{
			return sqlite_last_insert_rowid($this->resource);
		}

		return 0;
	}

	/**
	 *
	 * @return array
	 */
	public function fetchResult(QueryResult $a_queryResult, $fetchFlags = kRecordsetFetchBoth)
	{
		$sqliteFlags = 0;

		$r = $a_queryResult->resultResource;
		if ($this->m_implementation == self::kImplementationSQLite3)
		{
			if ($fetchFlags & kRecordsetFetchName)
				$sqliteFlags |= SQLITE3_ASSOC;
			if ($fetchFlags & kRecordsetFetchNumeric)
				$sqliteFlags |= SQLITE3_NUM;
			return $r->fetchArray($fetchFlags);
		}
		elseif ($this->m_implementation == self::kImplementationLegacy)
		{
			if ($fetchFlags & kRecordsetFetchName)
				$sqliteFlags |= SQLITE_ASSOC;
			if ($fetchFlags & kRecordsetFetchNumeric)
				$sqliteFlags |= SQLITE_NUM;
			return sqlite_fetch_array($r, $fetchFlags);
		}

		return null;
	}

	public function resetResult(QueryResult $a_queryResult)
	{
		$r = $a_queryResult->resultResource;
		if ($this->m_implementation == self::kImplementationSQLite3)
		{
			$result = $r->reset();
			if ($result)
			{
				$result = $r->fetchArray();
				$r->reset();
			}

			return $result;
		}
		elseif ($this->m_implementation == self::kImplementationLegacy)
		{
			return sqlite_rewind($r);
		}

		return false;
	}

	public function freeResult(QueryResult $a_queryResult)
	{
		$r = $a_queryResult->resultResource;
		if ($this->m_implementation == self::kImplementationSQLite3)
		{
			/**
			 *
			 * @note Due to GC shitty behavior
			 * SQLite db may have been destroyed before SQLite3Result
			 */
			@$r->finalize();
		}
		elseif ($this->m_implementation == self::kImplementationLegacy)
		{
			/* ... */
		}

		return true;
	}

	public function resultRowCount(QueryResult $a_queryResult)
	{
		$index = $a_queryResult->getCurrentRowIndex();

		// Urk, ugly
		if (!$this->resetResult($a_queryResult))
		{
			return 0;
		}

		$c = 0;
		while ($this->fetchResult($a_queryResult, kRecordsetFetchNumeric))
		{
			$c++;
		}

		$this->resetResult($a_queryResult);

		if ($index >= 0)
		{
			while ($index > 0)
			{
				$this->fetchResult($a_queryResult, kRecordsetFetchNumeric);
				$index--;
			}
		}

		return $c;
	}

	public function recordsetColumnArray(QueryResult $a_queryResult)
	{
		$res = array();
		$r = $a_queryResult->resultResource;
		if ($this->m_implementation == self::kImplementationSQLite3)
		{
			if (!(($r instanceof \SQLite3Result)))
			{
				/**
				 *
				 * @todo message
				 */
				throw new \Exception('Invalid result object');
			}

			$n = $r->numColumns();
			for ($i = 0; $i < $n; $i++)
			{
				$res[] = $r->columnName($i);
			}
			return $res;
		}
		elseif ($this->m_implementation == self::kImplementationLegacy)
		{
			$n = sqlite_num_fields($r);
			for ($i = 0; $i < $n; $i++)
			{
				$res[] = sqlite_field_name($r, $i);
			}

			return $res;
		}

		return null;
	}

	public function getAffectedRowCount(QueryResult $a_queryResult)
	{
		/**
		 *
		 * @bug unsafe if called after another query
		 */
		if ($this->m_implementation == self::kImplementationSQLite3)
		{
			return $this->resource->changes();
		}
		elseif ($this->m_implementation == self::kImplementationLegacy)
		{
			return sqlite_changes($this->resource);
		}

		return 0;
	}

	public function encloseElement($a_strElement)
	{
		if ($a_strElement == '*')
		{
			return $a_strElement;
		}
		return '[' . $a_strElement . ']';
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Datasource::getTableSet()
	 */
	public function getTableSet($name = null)
	{
		if ($name == $this->m_databaseName)
		{
			$db = new SQLiteTableSet($this, $name);
			return $db;
		}

		/**
		 *
		 * @todo attached databases
		 */

		throw new \InvalidArgumentException(
			$name . ' is not the SQLite database name (' . $this->m_databaseName . ')');
	}

	// default behavior
	// public abstract function getTableSetIterator()
	// public function tableSetExists($a_strTableSetName)
	public function getTableSetStructure(SQLObject $a_containerObject, $recursive = false)
	{
		$v = $this->createData(kDataTypeString);
		$v->import('table');
		$sql = 'SELECT * FROM ' . $this->encloseElement($this->m_databaseName) . '.' .
			$this->encloseElement('sqlite_master') . ' WHERE ' . $this->encloseElement('type') . '=' .
			$v->expressionString() . ' ORDER BY ' . $this->encloseElement('name') . ';';
		$query = new FormattedQuery($this, $sql);
		$records = $query->execute();
		if (!($records instanceof Recordset))
			throw new \Exception($sql);

		$name = ($a_containerObject == $this) ? $this->m_databaseName : $a_containerObject->getName();

		$structure = new TableSetStructure($this->structure, $name);

		foreach ($records as $row)
		{
			$ts = null;
			if ($recursive)
			{
				$ts = $this->getTableStructure($this->getTable($row['name']));
			}
			else
			{
				$ts = new TableStructure($structure, $row['name']);
			}

			if ($ts)
			{
				$structure->addTableStructure($ts);
			}
		}

		return $structure;
	}

	public function getTableStructure(Table $a_table)
	{
		$sql = 'PRAGMA table_info(\'' . $a_table->getName() . '\')';
		$queryRes = new Recordset($this, $this->executeQuery($sql), kRecordsetFetchName);
		if ($queryRes === false)
		{
			return false;
		}

		$s = $a_table->structure;
		$ts = new TableStructure(($s ? $s->parent() : null), $a_table->getName());

		foreach ($queryRes as $row)
		{
			$name = $row['name'];
			$typedef = parseDataTypeDefinition($row['type'], true);

			$f = new TableColumnStructure($ts, $name);
			$f->setProperty(kStructurePrimaryKey, ($row['pk'] == '1'));
			$f->setProperty(kStructureFieldTypename, $typedef['type']);
			$f->setProperty(kStructureAcceptNull, intval($row['notnull']) == 0);

			if ($typedef['size'] !== false)
			{
				$f->setProperty(kStructureDataSize, $typedef['size']);
			}

			if ($typedef['dec_size'] !== false)
			{
				$f->setProperty(kStructureDecimalCount, $typedef['dec_size']);
			}

			$ts->addColumnStructure($f);
		}

		return $ts;
	}

	// Methods

	/**
	 * Execute a query wich does not return a result
	 *
	 * @param string $a_strQuery
	 * @return bool
	 */
	public function resultlessQuery($a_strQuery)
	{
		//ns\Reporter::debug ($this, $a_strQuery);
		$errorMessage = '';
		$result = null;
		if ($this->m_implementation == self::kImplementationSQLite3)
		{
			if (!($this->resource instanceof \SQLite3))
			{
				throw new \Exception('No Datasource');
			}

			$result = $this->resource->exec($a_strQuery);
		}
		elseif ($this->m_implementation == self::kImplementationLegacy)
		{
			if (!$this->resource)
			{
				throw new \Exception('No Datasource');
			}
			$result = sqlite_query($this->resource, $a_strQuery, SQLITE_BOTH, $errorMessage);
		}

		if (!$result)
		{
			throw new \Exception('Query error: ' . $a_strQuery . ' / ' . $errorMessage);
		}

		return $result;
	}

	/**
	 * Main database name
	 *
	 * @var string
	 */
	private $m_databaseName;

	/**
	 * SQLite extension to use
	 *
	 * @var integer
	 */
	private $m_implementation;

	private $m_nowExpression;

	public static function initialize()
	{
		if (!self::initializeDatasourceData(get_called_class()))
			return;

		self::addDataType('TEXT', kDataTypeString);
		self::addDataType('VARCHAR', kDataTypeString);
		self::addDataType('INTEGER', kDataTypeInteger);
		self::addDataType('INTEGER', kDataTypeNumber);
		self::addDataType('REAL', kDataTypeDecimal);
		self::addDataType('REAL', kDataTypeNumber);
		self::addDataType('NUMERIC', kDataTypeNumber);
		self::addDataType('BLOB', kDataTypeBinary);
		self::setDefaultTypeName(kDataTypeTimestamp, 'TEXT');
	}
}

SQLiteDatasource::initialize();
