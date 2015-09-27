<?php
// ///////////////////////////////////////////////////////////////////////////////////////////////////
// NoreSources::php
// <tpl name="license" prepend="// "/>
//
// ///////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * PostgreSQL implementation of Datasource
 * 
 * @package PostgreSQL
 */
if (!defined("NS_FILE_NS"))
{
	die("ns.php must be included first");
}

if (NS_USE_NS_INCLUDE)
{
	ns::xinclude("extensions/pgsql/types");
	ns::xinclude("sql/providers");
	ns::xinclude("sql/queryresults");
	ns::xinclude("sql/queries");
	ns::xinclude("sql/Datasource");
	ns::xinclude("extensions/spl/spl");
	ns::xinclude("core/messagemanager");
	ns::xinclude("core/object");
}
else
{
	require_once (NS_PHP_PATH . "/extensions/pgsql/types.5.php");
	require_once (NS_PHP_PATH . "/sql/providers.5.php");
	require_once (NS_PHP_PATH . "/sql/queries.5.php");
	require_once (NS_PHP_PATH . "/sql/queryresults.5.php");
	require_once (NS_PHP_PATH . "/sql/Datasource.5.php");
	require_once (NS_PHP_PATH . "/extensions/spl/spl.5.php");
	require_once (NS_PHP_PATH . "/core/messagemanager.5.php");
	require_once (NS_PHP_PATH . "/core/object.5.php");
}

/**
 *
 * @author renaud
 *        
 *         Notes:
 *         - The term "Database" here, refers to PostgreSQL Structure
 *         - The term "Datasource" efers to PostgreSQL database
 */
class PostgreSQLDatasource extends Datasource implements ITableProvider, ITransactionBlock
{
	// construction - destruction
	public function __construct(SQLDatasourceStructure $a_structure = null)
	{
		parent::__construct($a_structure);
		$this->m_oDatabasePropertiesArray = null;
		$this->structureTableProviderDatabaseName = 'main';
		
		$type = array (
				'character varying',
				"varchar" 
		);
		foreach ($type as $name)
		{
			$this->addDataTypeName(new PostgreSQLVarCharDataType($this, $name, 0, 255));
		}
		
		$type = array (
				"text" 
		);
		foreach ($type as $name)
		{
			$this->addDataTypeName(new PostgreSQLStringDataType($this, $name));
		}
		
		$type = array (
				"bytes" 
		);
		foreach ($type as $name)
		{
			$this->addDataTypeName(new PostgreSQLBinaryDataType($this, $name));
		}
		
		$type = array (
				"timestamp",
				"timestamp without timezone" 
		);
		foreach ($type as $name)
		{
			$this->addDataTypeName(new SQLDatetimeDataType(get_class($this), $name));
		}
		
		$type = array (
				"date" 
		);
		foreach ($type as $name)
		{
			$this->addDataTypeName(new SQLDateDataType(get_class($this), $name));
		}
		
		$type = array (
				"time",
				"time without timezone" 
		);
		foreach ($type as $name)
		{
			$this->addDataTypeName(new SQLTimeDataType(get_class($this), $name));
		}
		
		$this->addDataTypeName(new SQLBooleanDataType(get_class($this), "boolean"));
		
		// number types
		$type = array (
				"smallint",
				"integer",
				"bigint",
				"int2",
				"int4",
				"int8",
				"serial",
				"big serial" 
		);
		foreach ($type as $name)
		{
			$this->addDataTypeName(new SQLNumberDataType($this, $name));
		}
		
		$type = array (
				"numeric",
				"decimal",
				"real",
				"double precision" 
		);
		foreach ($type as $name)
		{
			$this->addDataTypeName(new SQLDecimalNumberDataType(get_class($this), $name, 0, 1000, 15));
		}
		
		$this->setDefaultTypeName(kDataTypeBinary, "bytes");
		$this->setDefaultTypeName(kDataTypeBoolean, "boolean");
		$this->setDefaultTypeName(kDataTypeTimestamp, "timestamp");
		$this->setDefaultTypeName(kDataTypeNumber, "integer");
		$this->setDefaultTypeName(kDataTypeString, "text");
		$this->setDefaultTypeName(DATATYPE_TIME, "time");
	}

	public function __destruct()
	{
		parent::__destruct();
		if (is_resource($this->m_datasourceResource))
		{
			pg_close($this->m_datasourceResource);
		}
	}

	public function setStructureTableProviderDatabaseName($name)
	{
		$this->structureTableProviderDatabaseName = $name;
	}
	
	// ITransactionBlock implementation
	
	/**
	 *
	 * @see sources/sql/ITransactionBlock#startTransaction()
	 */
	function startTransaction()
	{
		/*
		 * $oQuery = new FormattedQuery($this, "START TRANSACTION;"); $oQuery->execute();
		 */
	}

	/**
	 *
	 * @see sources/sql/ITransactionBlock#commitTransaction()
	 */
	function commitTransaction()
	{
		/*
		 $oQuery = new FormattedQuery($this, "COMMIT;");
		$oQuery->execute();
		*/
	}

	/**
	 * @see sources/sql/ITransactionBlock#rollbackTransaction()
	 */
	function rollbackTransaction()
	{
		/*
		 $oQuery = new FormattedQuery($this, "ROLLBACK;");
		$oQuery->execute();
		*/
	}

	// ITableProvider implementation

	/**
	 * @todo Check behavior
	 */
	public function &tableObject($a_strName, $a_strAlias = null, $a_strClassName = null, $useAliasAsName = false)
	{
		$subStructure = null;
		if ($this->structure)
		{
			// Use the 'main' database if exists
			// This is a totally arbitrary decision and should be fixed
			$subStructure = $this->structure->offsetGet($this->structureTableProviderDatabaseName);
		}
		
		if ($subStructure)
		{
			$subStructure = $subStructure->offsetGet($a_strName);
		}

		$res = tableProviderGenericTableObjectMethod($this, $subStructure, $a_strName, $a_strAlias, $a_strClassName, $useAliasAsName);
		return $res;
	}

	public function tableIterator()
	{
		$subStructure = null;
		if ($this->structure)
		{
			// Use the 'main' database if exists
			// This is a totally arbitrary decision and should be fixed
			return $this->structure->offsetGet($this->structureTableProviderDatabaseName);
		}

		return null;
	}

	public function tableExists($a_strName)
	{
		if ($this->structure)
		{
			$this->structure->offsetExists($a_strName);
		}

		return true;
	}

	// Datasource implementation

	/**
	 * Connection
	 *
	 * @param array $a_aParameters parameters
	 * @return boolean
	 */
	public function connect($a_aParameters)
	{
		if ($this->resource())
		{
			$this->disconnect();
		}

		if ( !(array_key_exists(kConnectionParameterHostname, $a_aParameters)
				&& array_key_exists(kConnectionParameterUsername, $a_aParameters)))
		{
			return ns\Reporter::instance()->addError($this,__METHOD__."(): Parameters are missing. 'host', 'user', ['password' and 'Database'] must be provided.",__FILE__, __LINE__);
		}

		$connectionString = "host = '" . $a_aParameters[kConnectionParameterHostname] . "'";
		$connectionString .= " user = '" . $a_aParameters[kConnectionParameterUsername] . "'";
		if (array_key_exists(kConnectionParameterPassword, $a_aParameters))
		{
			$connectionString .= " password = '" . $a_aParameters[kConnectionParameterPassword] . "'";
		}

		if (array_key_exists(kConnectionParameterDatabasename, $a_aParameters))
		{
			$connectionString .= " dbname = '" . $a_aParameters[kConnectionParameterDatabasename] . "'";
		}

		if (function_exists("pg_connect"))
		{
			$this->m_datasourceResource = pg_connect($connectionString);
		}
		else
		{
			return ns\Reporter::instance()->addError($this,__METHOD__."(): PostgreSQL extension is not installed", __FILE__, __LINE__);
		}

		if (!$this->m_datasourceResource)
		{
			return ns\Reporter::instance()->addError($this,__METHOD__."(): Unable to connect to Database ".$a_aParameters[kConnectionParameterHostname], __FILE__, __LINE__);
		}

		return true;
	}

	/**
	 * @see sources/sql/Datasource#disconnect()
	 * @return bool
	 */
	protected function disconnect()
	{
		return @pg_close($this->resource());
	}

	/**
	 * @see sources/sql/Datasource#executeQuery()
	 * @return QueryResult
	 */
	public function executeQuery($a_strQuery)
	{
		if ($DEBUG_SQL)
		{
			ns\Reporter::instance()->addDebug($this, $a_strQuery);
		}

		$result = @pg_query($this->resource(), $a_strQuery);
		if ($result === false)
		{
			return ns\Reporter::instance()->addError($this,__METHOD__."(): Query error: ".$a_strQuery." / ".pg_last_error($this->resource()));
		}

		return $result;
	}

	/**
	 * @see sources/sql/Datasource#lastInsertId()
	 * @return integer
	 */
	public function lastInsertId()
	{
		/**
		 * @todo see http://www.php.net/manual/fr/function.pg-last-oid.php
		 */
		//return pg_last_oid($this->resource());
		
		$query = new FormattedQuery($this, "SELECT LASTVAL()");
		$queryRes = $query->execute();
		if ($queryRes === false)
		{
			return false;
		}
		
		$firstRow = $queryRes->current();
		return $firstRow[0]; 
	}

	/**
	 * @see sources/sql/Datasource#fetchResult()
	 * @return
	 */
	public function fetchResult(QueryResult $a_queryResult)
	{
		return pg_fetch_array($a_queryResult->resultResource);
	}

	public function resetResult(QueryResult $a_queryResult)
	{
		$r = $a_queryResult->resultResource;
		return pg_result_seek($r, 0);
	}
	
	/**
	 * @see sources/sql/Datasource#freeResult()
	 */
	public function freeResult(QueryResult $a_queryResult)
	{
		if (is_resource($a_queryResult->resultResource))
		{
			return pg_free_result($a_queryResult->resultResource);
		}
		return false;
	}

	/**
	 * @see sources/sql/Datasource#resultRowCount()
	 * @return integer
	 */
	public function resultRowCount(QueryResult $a_queryResult)
	{
		return pg_num_rows($a_queryResult->resultResource);
	}

	/**
	 * @see sources/sql/Datasource#recordsetColumnArray()
	 * @return array
	 */
	public function recordsetColumnArray(QueryResult $a_queryResult)
	{
		$res = array();
		$n = pg_num_fields($a_queryResult->resultResource);
		for ($i = 0; $i < $n; $i++)
		{
			$res[] = pg_field_name($a_queryResult->resultResource, $i);
		}
		return $res;
	}

	/**
	 * @see sources/sql/Datasource#affectedRowCount()
	 * @return integer
	 */
	public function affectedRowCount(QueryResult $a_queryResult)
	{
		return pg_affected_rows($a_queryResult->datasource->resource());
	}

	/**
	 * @see sources/sql/Datasource#encloseElement()
	 * @return string
	 */
	public function encloseElement($a_strElement)
	{
		if ($a_strElement != "*")
		{
			if (function_exists("pg_escape_identifier"))
			{
				return pg_escape_identifier($this->resource(), $a_strElement);
			}
			return '"' . $a_strElement . '"';
		}
		
		return $a_strElement;
	}

	// Database relative

	// default behavior
	// public function getDatabaseIterator()
	// public function databaseExists($a_strDatabaseName)
		
	/**
	 * @see sources/sql/Datasource#tableArray()
	 * @return array
	 */
	public function &tableArray(SQLObject $a_oContainerObject)
	{
		return null;
	}

	/**
	 * @see sources/sql/Datasource#fieldArray()
	 * @return array
	 */
	public function &fieldArray(Table $a_table)
	{
		return null;
	}

	// Datasource implementation

	/**
	 * @return bool
	 */
	public function caseSensitiveTableNames()
	{
		return true;
	}

	/**
	 * An array object of Database names
	 *
	 * @var ArrayObject
	 */
	protected $m_oDatabasePropertiesArray;
	
	protected $structureTableProviderDatabaseName;

}

?>
