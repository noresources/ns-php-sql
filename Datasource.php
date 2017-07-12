<?php

/**
 * Copyright © 2012-2017 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources as ns;

require_once (NS_PHP_CORE_PATH . '/arrays.php');
require_once (__DIR__ . '/base.php');

/**
 * Interface for Database system that support
 * Transaction commit & rollbacks
 */
interface ITransactionBlock
{

	/**
	 * Begin transaction block of instruction
	 */
	function startTransaction();

	/**
	 * Commit all changes since last startTransaction() call
	 */
	function commitTransaction();

	/**
	 * Cancel all changes since last startTransaction() call
	 */
	function rollbackTransaction();
}

/**
 * A Datasource connection
 *
 * This object provides a connection to a Datasource.
 *
 * Implements a default behavior to access tables
 */
abstract class Datasource extends SQLObject implements ITableSetProvider
{
	
	/**
	 * null keyword
	 *
	 * @var string
	 */
	const kStringKeywordNull = 'null';
	
	/**
	 * Keyword or value for boolean TRUE
	 *
	 * @var string
	 */
	const kStringKeywordTrue = 'true';
	
	/**
	 * Keyword or value for boolean FALSE
	 *
	 * @var string
	 */
	const kStringKeywordFalse = 'false';
	
	/**
	 * AUTO INCREMENT keywork
	 *
	 * @var string
	 */
	const kStringKeywordAutoincrement = 'sql.string.autoinc';
	const kStringKeywordJoinNatural = kJoinNatural;
	const kStringKeywordJoinCross = kJoinCross;
	const kStringKeywordJoinOuter = kJoinOuter;
	const kStringKeywordJoinInner = kJoinInner;
	const kStringKeywordJoinLeft = kJoinLeft;
	const kStringKeywordJoinRight = kJoinRight;
	
	/**
	 * PHP-style string format for timestamps
	 *
	 * @var string
	 */
	const kStringTimestampFormat = 'sql.string.timestamp';
	
	/**
	 * Database implementation to use
	 *
	 * @var string
	 */
	const kStringClassNameDatabase = 'sql.string.clsdb';
	
	/**
	 * SQLDatabaseManipulator implementation to use
	 *
	 * @var string
	 */
	const kStringClassNameTableSetManipulator = 'sql.string.clsdbm';
	
	/**
	 * Table implementation to use
	 *
	 * @var string
	 */
	const kStringClassNameTable = 'sql.string.clst';
	
	/**
	 * TableManipulator implementation to use
	 *
	 * @var string
	 */
	const kStringClassNameTableManipulator = 'sql.string.clstm';

	/**
	 * Create an instance of a Datasource object
	 *
	 * @param arrat|ArrayAccess $settings Table of settings with at least the sql.source.classname key
	 * @param bool$connect If @c true, Attempt to connect to datasource with
	 *        the settings given in @param $settings
	 *       
	 * @return Datasource
	 */
	public static function create($settings, $connect = false)
	{
		if (!\is_array($settings))
		{
			$settings = ns\to_array($settings, kConnectionParameterHostname);
		}
		
		$cls = ns\array_keyvalue($settings, kConnectionParameterClassname, null);
		if (!is_string($cls))
		{
			return ns\Reporter::error(__CLASS__, __METHOD__ . ' Unable to create Datasource without ' . kConnectionParameterClassname . ' parameter', __FILE__, __LINE__);
		}
		
		if (!(class_exists($cls) && is_a($cls, __CLASS__, true)))
		{
			return ns\Reporter::error(__CLASS__, __METHOD__ . ': Invalid class name "' . $cls . '"', __FILE__, __LINE__);
		}
		
		$o = new $cls();
		if ($connect)
		{
			$connected = $o->connect($settings);
			if (!$connected)
			{
				return ns\Reporter::error(__CLASS__, __METHOD__ . ': Failed to connect', __FILE__, __LINE__);
			}
		}
		
		$structureFile = ns\array_keyvalue($settings, kConnectionParameterStructureFile, null);
		if ($structureFile && file_exists($structureFile))
		{
			$structure = StructureElement::createFromXmlFile($structureFile, null);
			if ($structure && ($structure instanceof DatasourceStructure))
			{
				$o->setStructure($structure);
			}
		}
		
		return $o;
	}

	/**
	 *
	 * @param DatasourceStructure $a_structure Datasource structure
	 */
	protected function __construct(DatasourceStructure $a_structure = null)
	{
		parent::__construct($a_structure);
		$this->m_datasourceResource = null;
		$this->m_datasourceFlags = 0;
		$this->m_dataTypeNames = array ();
		$this->m_defaultTypeNames = array ();
		$this->m_datasourceStrings = array (
				// Keyworks
				self::kStringKeywordNull => 'NULL',
				self::kStringKeywordTrue => '1',
				self::kStringKeywordFalse => '0',
				self::kStringKeywordAutoincrement => 'AUTO INCREMENT',
				self::kStringKeywordJoinNatural => 'NATURAL JOIN',
				self::kStringKeywordJoinCross => 'CROSS JOIN',
				self::kStringKeywordJoinOuter => 'OUTER JOIN',
				self::kStringKeywordJoinInner => 'INNER JOIN',
				self::kStringKeywordJoinLeft => 'LEFT JOIN',
				self::kStringKeywordJoinRight => 'RIGHT JOIN',
				// Class names
				self::kStringClassNameDatabase => __NAMESPACE__ . '\\Database',
				self::kStringClassNameTable => __NAMESPACE__ . '\\Table',
				// Other
				self::kStringTimestampFormat => 'Y-m-d H:i:s' 
		);
	}

	/**
	 * Default destructor
	 * Disconnect from data source if non-persistent
	 */
	public function __destruct()
	{
		if (!($this->m_datasourceFlags & kConnectionPersistent))
		{
			$this->disconnect();
		}
	}

	public function __get($member)
	{
		if ($member == 'datasource')
		{
			return $this;
		}
		elseif ($member == 'resource')
		{
			return $this->m_datasourceResource;
		}
		elseif ($member == 'flags')
		{
			return $this->m_datasourceFlags;
		}
		
		return parent::__get($member);
	}
	
	// ITableSetProvider
	

	/**
	 * Provide a TableSet object
	 *
	 * @param string $a_name
	 * @return Database
	 */
	public function getTableSet($a_name)
	{
		$subStructure = null;
		if ($this->structure)
		{
			$subStructure = $this->structure->offsetGet($a_name);
		}
		
		$result = new Database($this, $a_name, $subStructure);
		
		return $result;
	}

	/**
	 * Provides an iterator on all the tableset names of the Datasource
	 *
	 * @return Iterator
	 */
	public function getTableSetIterator()
	{
		if ($this->m_structure)
		{
			return $this->m_structure;
		}
		
		return null;
	}

	/**
	 * Check existence of a tableset name
	 * The default implementation is tu check the SQL structure if available
	 * or to consider any name as valid
	 *
	 * @tono check in structure and/or source
	 *
	 * @param string $tablesetName
	 * @return bool
	 */
	public function tableSetExists($tablesetName)
	{
		return ($this->m_structure) ? ($this->m_structure->offsetExists($tablesetName)) : true;
	}
	
	// Datasource API
	

	/**
	 * Set the Datasource structure
	 *
	 * @param DatasourceStructure $a_structure Datasource structure
	 */
	public final function setStructure(DatasourceStructure $a_structure)
	{
		$this->m_structure = $a_structure;
	}

	/**
	 * Open a connection
	 *
	 * @param array $a_aParameters connection parameters (depend on connection type)
	 * @return bool
	 */
	public abstract function connect($a_aParameters);

	/**
	 * Disconnect from connection
	 */
	protected abstract function disconnect();

	/**
	 * Execute a query with the given parameters
	 *
	 * @param string $a_queryString Query string
	 * @return QueryResult
	 */
	public abstract function executeQuery($a_queryString);

	/**
	 * Fetch query result
	 *
	 * @param mixed $a_queryResult query result ressource
	 * @return array
	 */
	public abstract function fetchResult(QueryResult $a_queryResult, $fetchFlags = kRecordsetFetchBoth);

	/**
	 * Reset the result cursor before the first record
	 *
	 * @param QueryResult $a_queryResult
	 * @return @c true on success and if recordset contains at least one element
	 */
	public abstract function resetResult(QueryResult $a_queryResult);

	/**
	 * Free resource associated to a query result
	 *
	 * @param QueryResult $a_queryResult
	 */
	public abstract function freeResult(QueryResult $a_queryResult);

	/**
	 *
	 * @return last auto increment insert id
	 */
	public abstract function lastInsertId();

	/**
	 * Number of row returned by query
	 *
	 * @param QueryResult $a_queryResult
	 * @return integer
	 */
	public abstract function resultRowCount(QueryResult $a_queryResult);

	/**
	 * Provide an ArrayObject containing all column names in a
	 * recordset
	 *
	 * @param mixed $a_queryResult A resource representing the recordset
	 * @return array
	 */
	public abstract function recordsetColumnArray(QueryResult $a_queryResult);

	/**
	 * Number of affected row by query
	 *
	 * @param mixed $a_queryResult query result ressource
	 * @return integer
	 */
	public abstract function affectedRowCount(QueryResult $a_queryResult);

	/**
	 * Protect SQL element following Database management system requirements
	 *
	 * @param string $a_strElement
	 */
	public abstract function encloseElement($a_strElement);

	/**
	 * Get the structures contained in the given table container element
	 *
	 * @p $a_containerObject can be a Datasource ($this) or a
	 * IDatabase, depending of Datasource model
	 *
	 * @param SQLObject $a_containerObject
	 * @param boolean $recursive Fill sub elements
	 *       
	 * @return DatabaseStructure
	 */
	public abstract function getTableSetStructure(SQLObject $a_containerObject, $recursive = false);

	/**
	 * Get the table structure
	 *
	 * @param Table $a_table
	 */
	public abstract function getTableStructure(Table $a_table);

	/**
	 * Get datasource-specific strings
	 *
	 * @param string $a_key String identifier
	 * @return string String value
	 */
	public final function getDatasourceString($a_key)
	{
		return \array_key_exists($a_key, $this->m_datasourceStrings) ? $this->m_datasourceStrings [$a_key] : null;
	}

	/**
	 * Get connection resource
	 *
	 * @return resource
	 */
	public final function resource()
	{
		return $this->m_datasourceResource;
	}

	/**
	 *
	 * @param mixed $mixed SQL data type (number), Datasource-specific type name,
	 *        TableColumn or TableColumnStructure
	 */
	public function createData($dataType)
	{
		$sqlType = self::guessDataType($dataType);
		if ($sqlType === false)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Unable to find type', __FILE__, __LINE__);
		}
		
		$structure = guessStructureElement($dataType);
		
		if ($sqlType == kDataTypeNull)
		{
			return new NullData($this);
		}
		elseif ($sqlType == kDataTypeString)
		{
			return new StringData($this, $structure);
		}
		elseif ($sqlType == kDataTypeNumber)
		{
			return new NumberData($this, $structure);
		}
		elseif ($sqlType == kDataTypeTimestamp)
		{
			return new TimestampData($this, $structure);
		}
		elseif ($sqlType == kDataTypeBoolean)
		{
			return new BooleanData($this);
		}
		elseif ($sqlType == kDataTypeBinary)
		{
			return new StringData($this);
		}
		
		return null;
	}

	/**
	 * Get the default type name for a standard type
	 *
	 * @param enum $a_sqlType one of the kDataType*
	 * @return string
	 */
	public final function getDefaultTypeName($a_sqlType)
	{
		if (\array_key_exists($a_sqlType, $this->m_defaultTypeNames))
		{
			return $this->m_defaultTypeNames [$a_sqlType];
		}
		
		return ns\Reporter::error($this, __METHOD__ . '(): No default type for ' . strval($a_sqlType), __FILE__, __LINE__);
	}

	/**
	 *
	 * @param mixed $dataType
	 * @return integer SQL type index or <code>false</code> if type can't be
	 *         found
	 */
	protected function guessDataType($dataType)
	{
		if (is_numeric($dataType))
		{
			return $dataType;
		}
		elseif (is_string($dataType))
		{
			$dataType = strtolower($dataType);
			if (\array_key_exists($dataType, $this->m_dataTypeNames))
			{
				return $this->m_dataTypeNames [$dataType] ['type'];
			}
		}
		elseif ($dataType instanceof TableColumnStructure)
		{
			return $dataType->getProperty(self::kStructureDatatype);
		}
		elseif ($dataType instanceof TableColumn)
		{
			if ($dataType->structure)
			{
				return $dataType->structure->getProperty(self::kStructureDatatype);
			}
		}
		
		return false;
	}

	/**
	 *
	 * @param string $a_typeName Datasource type name
	 * @param integer $sqlType SQL type index
	 */
	protected function addDataType($a_typeName, $sqlType, $className = null)
	{
		$a_typeName = strtolower($a_typeName);
		if (\array_key_exists($a_typeName, $this->m_dataTypeNames))
		{
			ns\Reporter::fatalError($this, __METHOD__ . '(): ' . $a_typeName . ' already exists', __FILE__, __LINE__);
		}
		
		$this->m_dataTypeNames [$a_typeName] = array (
				'type' => $sqlType,
				'class' => $className 
		);
		
		if (!\array_key_exists($sqlType, $this->m_defaultTypeNames))
		{
			$this->m_defaultTypeNames [$sqlType] = $a_typeName;
		}
	}

	protected function setDatasourceFlags($flags)
	{
		$this->m_datasourceFlags = $flags;
	}

	protected function setDefaultTypeName($sqlType, $a_typeName)
	{
		$this->m_defaultTypeNames [$sqlType] = $a_typeName;
	}

	/**
	 * Set datasource-dependant string or keyword
	 *
	 * @param integer $a_key string/keyword index
	 * @param string $a_value value
	 */
	protected function setDatasourceString($a_key, $a_value)
	{
		$this->m_datasourceStrings [$a_key] = $a_value;
	}

	/**
	 * Connection resource
	 *
	 * @var mixed
	 */
	protected $m_datasourceResource;

	/**
	 * List of all available datatypes
	 * @note keys are stored in lowercase
	 *
	 * @var array Keys: type names; Values: SQL type index
	 */
	protected $m_dataTypeNames;

	/**
	 * List of default datasource type name for each data type
	 *
	 * @var array
	 */
	protected $m_defaultTypeNames;

	/**
	 * Capabilities and state
	 *
	 * @var integer
	 */
	private $m_datasourceFlags;

	/**
	 *
	 * @var array
	 */
	private $m_datasourceStrings;
}
