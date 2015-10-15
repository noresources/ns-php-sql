<?php

/**
 * Copyright © 2012-2015 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources as ns;
use \SQLite3;
use Exception;

/**
 *
 * @todo Update to use SQL*Structure
 * @author renaud
 *        
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
		if (!$this->postCreation($a_structure))
		{
			return false;
		}
		
		$t = new Table($this->m_provider, $a_structure->getName());
		$strQuery = 'CREATE TABLE ' . $t->expressionString() . ' (';
		
		$first = true;
		foreach ($a_structure as $name => $field)
		{
			if (!$first)
			{
				$strQuery .= ",\n";
			}
			$first = false;
			
			$strQuery .= $this->m_datasource->encloseElement($field->getName());
			$type = $field->getProperty(kStructureDatatype);
			$auto = $field->getProperty(kStructureAutoincrement);
			$acceptNull = $field->getProperty(kStructureAcceptNull);
			if ($type !== null)
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

class SQLiteDatasource extends Datasource implements ITransactionBlock, ITableProvider
{
	// construction - destruction
	const MEMORY_DATABASENAME = ':memory:';
	const DEFAULT_DATABASENAME = 'main';
	const IMPLEMENTATION_sqlite = 1;
	const IMPLEMENTATION_sqlite3 = 2;

	public function __construct()
	{
		parent::__construct();
		$this->setDatasourceString(self::kStringClassNameTableManipulator, __NAMESPACE__ . '\\SQLiteTableManipulator');
		
		if (extension_loaded('sqlite3'))
		{
			$this->m_implementation = self::IMPLEMENTATION_sqlite3;
		}
		elseif (extension_loaded('sqlite'))
		{
			$this->m_implementation = self::IMPLEMENTATION_sqlite;
		}
		else
		{
			$this->m_implementation = null;
		}
		
		$this->m_databaseName = null;
		
		$this->addDataType('TEXT', kDataTypeString, __NAMESPACE__ . '\\SQLiteStringData');
		$this->addDataType('VARCHAR', kDataTypeString, __NAMESPACE__ . '\\SQLiteStringData');
				
		$this->addDataType('INTEGER', kDataTypeNumber);
		$this->addDataType('REAL', kDataTypeNumber);
		$this->addDataType('NUMERIC', kDataTypeNumber);
		$this->addDataType('BLOB', kDataTypeBinary, __NAMESPACE__ . '\\SQLiteBinaryData');
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

	public function tableObject($a_name, $a_strAlias = null, $a_className = null, $useAliasAsName = false)
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
		
		$res = tableProviderGenericTableObjectMethod($this, $subStructure, $a_name, $a_strAlias, $a_className, $useAliasAsName);
		return $res;
	}

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
		}
		
		if ($a_mode & kObjectQueryDatasource)
		{
			$a = $this->getDatabaseStructure($this, false);
			return (($a instanceof DatabaseStructure) && $a->offsetExists($a_name) && ($a [$a_name] instanceof TableStructure));
		}
		
		return $result;
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
		if ($this->resource())
		{
			$this->disconnect();
		}
		
		// Min requirements
		if (!array_key_exists(kConnectionParameterHostname, $a_aParameters) && !array_key_exists(kConnectionParameterFilename, $a_aParameters))
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Parameters are missing. "host" or "filename"] must be provided.', __FILE__, __LINE__);
		}
		
		$this->m_databaseName = ns\array_keyvalue($a_aParameters, kConnectionParameterDatabasename, 'main');
		$fileName = ns\array_keyvalue($a_aParameters, kConnectionParameterFilename, ns\array_keyvalue($a_aParameters, kConnectionParameterHostname, null));
		
		// echo_line('db: ' . $this->m_databaseName);
		// echo_line('file: ' . $fileName);
		
		if ($fileName != self::MEMORY_DATABASENAME)
		{
			if (file_exists($fileName))
			{
				$fileName = realpath($fileName);
			}
			
			if (!(file_exists($fileName) || ns\array_keyvalue($a_aParameters, kConnectionParameterCreate, false)))
			{
				return ns\Reporter::error($this, __METHOD__ . '(): Invalid file "' . $fileName . '"', __FILE__, __LINE__);
			}
		}
		
		$errorMessage = '';
		if ($this->m_implementation == self::IMPLEMENTATION_sqlite3)
		{
			$flags = 0;
			$ekey = '';
			
			if (ns\array_keyvalue($a_aParameters, kConnectionParameterCreate, false))
			{
				$flags |= SQLITE3_OPEN_CREATE;
			}
			
			if (ns\array_keyvalue($a_aParameters, kConnectionParameterReadOnly, false))
			{
				$flags |= SQLITE3_OPEN_READONLY;
			}
			else
			{
				$flags |= SQLITE3_OPEN_READWRITE;
			}
			
			try
			{
				$this->m_datasourceResource = new SQLite3($fileName, $flags, $ekey);
			}
			catch (Exception $e)
			{
				return ns\Reporter::error($this, __METHOD__ . '(): Unable to open database "' . $fileName . '" :' . $e->getMessage(), __FILE__, __LINE__);
			}
		}
		elseif ($this->m_implementation == self::IMPLEMENTATION_sqlite)
		{
			$mode = 0666;
			if (ns\array_keyvalue($a_aParameters, kConnectionParameterReadOnly, false))
			{
				$mode = 0444;
			}
			
			$this->m_datasourceResource = sqlite_open($fileName, $mode, $errorMessage);
		}
		else
		{
			$errorMessage = 'No implementation found';
		}

		if (strlen($errorMessage))
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Unable to connect to database ' . basename($fileName) . ': ' . $errorMessage, __FILE__, __LINE__);
		}

		// Use attach rather than open
		if ($this->m_databaseName != self::DEFAULT_DATABASENAME)
		{
			$str = 'attach database \'' . $fileName . '\' as ' . $this->encloseElement($this->m_databaseName) . ';';
			//echo_line($str);
			$res = $this->executeQuery($str);
			return ($res != false);
		}

		return true;
	}

	protected function disconnect()
	{
		if ($this->m_implementation == self::IMPLEMENTATION_sqlite3)
		{
			if (!($this->resource() instanceof SQLite3))
			{
				return false;
			}

			$this->resource()->close();
		}
		elseif ($this->m_implementation == self::IMPLEMENTATION_sqlite)
		{
			if (!$this->resource())
			{
				return false;
			}
			sqlite_close($this->resource());
		}
		return true;
	}

	public function createData($dataType)
	{
		if (array_key_exists($dataType, $this->m_dataTypeNames))
		{
			$a = $this->m_dataTypeNames [$dataType];
			$sqlType = $a ['type'];
			$structure = guessStructureElement($sqlType);

			$d = null;
			if ($a ['class'])
			{
				$cls = $a ['class'];
				return (new $cls($this, $structure));
			}
		}
		
		if ($sqlType = $this->guessDataType($dataType))
		{
			$structure = guessStructureElement($sqlType);
			if ($sqlType == kDataTypeString)
			{
				return (new SQLiteStringData($this, $structure));
			}
			elseif ($sqlType == kDataTypeBinary)
			{
				return (new SQLiteBinaryData($this, $structure));
			}
		}
		
		return parent::createData($dataType);
	}
	
	public function executeQuery($a_strQuery)
	{
		$errorMessage = '';
		$result = null;
		if ($this->m_implementation == self::IMPLEMENTATION_sqlite3)
		{
			if (!($this->resource() instanceof SQLite3))
			{
				return ns\Reporter::error($this, __METHOD__ . '(): No Datasource');
			}

			$result = $this->resource()->query($a_strQuery);
		}
		elseif ($this->m_implementation == self::IMPLEMENTATION_sqlite)
		{
			if (!$this->resource())
			{
				return ns\Reporter::error($this, __METHOD__ . '(): No Datasource');
			}

			$result = sqlite_query($this->resource(), $a_strQuery, SQLITE_BOTH, $errorMessage);
		}

		if (!$result)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Query error: ' . $a_strQuery . ' / ' . $errorMessage);
		}

		return $result;
	}

	public function lastInsertId()
	{
		if ($this->m_implementation == self::IMPLEMENTATION_sqlite3)
		{
			return $this->resource()->lastInsertRowID();
		}
		elseif ($this->m_implementation == self::IMPLEMENTATION_sqlite)
		{
			return sqlite_last_insert_rowid($this->resource());
		}

		return 0;
	}

	public function fetchResult(QueryResult $a_queryResult)
	{
		$r = $a_queryResult->resultResource;
		if ($this->m_implementation == self::IMPLEMENTATION_sqlite3)
		{
			return $r->fetchArray();
		}
		elseif ($this->m_implementation == self::IMPLEMENTATION_sqlite)
		{
			return sqlite_fetch_array($r);
		}

		return null;
	}

	public function resetResult(QueryResult $a_queryResult)
	{
		$r = $a_queryResult->resultResource;
		if ($this->m_implementation == self::IMPLEMENTATION_sqlite3)
		{
			return $r->reset();
		}
		elseif ($this->m_implementation == self::IMPLEMENTATION_sqlite)
		{
			return sqlite_rewind($r);
		}

		return false;
	}

	public function freeResult(QueryResult $a_queryResult)
	{
		$r = $a_queryResult->resultResource;
		if ($this->m_implementation == self::IMPLEMENTATION_sqlite3)
		{
			$r->finalize();
		}
		elseif ($this->m_implementation == self::IMPLEMENTATION_sqlite)
		{
			/* ... */
		}

		return true;
	}

	public function resultRowCount(QueryResult $a_queryResult)
	{
		$index = $a_queryResult->currentRowIndex();

		// Urk, ugly
		if (!$this->resetResult($a_queryResult))
		{
			return 0;
		}

		$c = 0;
		while ($this->fetchResult($a_queryResult))
		{
			$c++;
		}

		$this->resetResult($a_queryResult);

		if ($index >= 0)
		{
			while ($index > 0)
			{
				$this->fetchResult($a_queryResult);
				$index--;
			}
		}

		return $c;
	}

	public function recordsetColumnArray(QueryResult $a_queryResult)
	{
		$res = array ();
		$r = $a_queryResult->resultResource;
		if ($this->m_implementation == self::IMPLEMENTATION_sqlite3)
		{
			if (!(($r instanceof SQLite3Result)))
			{
				/**
				 *
				 * @todo message
				 */
				return ns\Reporter::error($this, __METHOD__ . '(): Invalid result object');
			}

			$n = $r->numColumns();
			for($i = 0; $i < $n; $i++)
			{
				$res [] = $r->columnName($i);
			}
			return $res;
		}
		elseif ($this->m_implementation == self::IMPLEMENTATION_sqlite)
		{
			$n = sqlite_num_fields($r);
			for($i = 0; $i < $n; $i++)
			{
				$res [] = sqlite_field_name($r, $i);
			}

			return $res;
		}

		return null;
	}

	public function affectedRowCount(QueryResult $a_queryResult)
	{
		/**
		 * @bug unsafe if called after another query
		 */
		if ($this->m_implementation == self::IMPLEMENTATION_sqlite3)
		{
			return $this->resource()->changes();
		}
		elseif ($this->m_implementation == self::IMPLEMENTATION_sqlite)
		{
			return sqlite_changes($this->resource());
		}

		return 0;
	}

	public function encloseElement($a_strElement)
	{
		return $a_strElement;
	}

	// IDatabaseProvider implementation
	
	public function getDatabase($a_name, $a_className = null)
	{
		if (!is_string($a_name))
		{
			$a_name = 'main';
		}

		if (!is_string($a_className))
		{
			$a_className = $this->getDatasourceString(self::kStringClassNameDatabase);
		}

		$subStructure = null;
		if ($this->structure)
		{
			$subStructure = $this->structure->offsetGet($a_name);
		}

		$result = new $a_className($this, $a_name, $subStructure);
		return $result;
	}

	// default behavior
	// public abstract function getDatabaseIterator()
	// public function databaseExists($a_strDatabaseName)

	public function getDatabaseStructure(SQLObject $a_containerObject, $recursive = false)
	{
		$v = $this->createData(kDataTypeString);
		$v->import('table');
		$queryStr = 'SELECT * ' . 'FROM ' . $this->encloseElement('sqlite_master') . ' WHERE ' . $this->encloseElement('type') . '=' . $v->expressionString() . ' ORDER BY ' . $this->encloseElement('name') . ';';
		$query = new FormattedQuery($this, $queryStr);
		$queryRes = $query->execute();
		if ($queryRes === false)
		{
			return false;
		}

		$name = ($a_containerObject == $this) ? $this->m_databaseName : $a_containerObject->getName();
		
		$structure = new DatabaseStructure($this->structure, $name);
		
		foreach ($queryRes as $row)
		{
			$ts = null;
			if ($recursive)
			{
				$ts = $this->getTableStructure($this->tableObject($row['name']));
			}
			else 
			{
				$ts = new TableStructure($structure, $row ['name']);
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
		// echo_line($queryStr);
		$queryStr = 'PRAGMA table_info(\'' . $a_table->getName() . '\')';
		$queryRes = new Recordset($this, $this->executeQuery($queryStr));
		if ($queryRes === false)
		{
			return false;
		}

		$s = $a_table->structure;
		$ts = new TableStructure(($s ? $s->parent() : null), $a_table->getName());
		
		foreach ($queryRes as $row)
		{
			$name = $row ['name'];
			$typedef = parseDataTypeDefinition($row ['type'], true);
			
			$f = new TableFieldStructure($ts, $name);
			$f->setProperty(kStructurePrimaryKey, ($row ['pk'] == '1'));
			$f->setPdroperty(kStructureFieldTypename, $typedef ['type']);
			$f->setProperty(kStructureAcceptNull, intval($row ['notnull']) == 0);
			
			if ($typedef ['size'] !== false)
			{
				$f->setProperty(kStructureDataSize, $typedef ['size']);
			}
			
			if ($typedef ['dec_size'] !== false)
			{
				$f->setProperty(kStructureDecimalCount, $typedef ['dec_size']);
			}
			
			$ts->addFieldStructure($f);
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
		$errorMessage = '';
		$result = null;
		if ($this->m_implementation == self::IMPLEMENTATION_sqlite3)
		{
			if (!($this->resource() instanceof SQLite3))
			{
				return ns\Reporter::error($this, __METHOD__ . '(): No Datasource', __FILE__, __LINE__);
			}
				
			$result = $this->resource()->exec($a_strQuery);
		}
		elseif ($this->m_implementation == self::IMPLEMENTATION_sqlite)
		{
			if (!$this->resource())
			{
				return ns\Reporter::error($this, __METHOD__ . '(): No Datasource');
			}
			$result = sqlite_query($this->resource(), $a_strQuery, SQLITE_BOTH, $errorMessage);
		}

		if (!$result)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Query error: ' . $a_strQuery . ' / ' . $errorMessage, __FILE__, __LINE__);
		}

		return $result;
	}

	/**
	 * Main database name 
	 * @var string
	 */
	protected $m_databaseName;

	/**
	 * SQLite extension to use
	 * @var integer
	 */
	protected $m_implementation;
}
