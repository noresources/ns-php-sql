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
use NoreSources\Reporter;

require_once (__DIR__ . '/../base.php');
require_once (NS_PHP_CORE_PATH . '/arrays.php');

/**
 * Notes:
 * - The term "TableSet" here, refers to PostgreSQL Structure
 * - The term "Datasource" efers to PostgreSQL database
 */
class PostgreSQLDatasource extends Datasource implements ITableProvider, ITransactionBlock
{
	const kDefaultTableSetName = 'public';
	
	// construction - destruction
	/**
	 *
	 * @param DatasourceStructure $a_structure
	 */
	public function __construct(DatasourceStructure $a_structure = null)
	{
		parent::__construct($a_structure);
		$this->activeTableSetName = self::kDefaultTableSetName;
		
		$type = array (
				'character varying',
				'varchar' 
		);
		foreach ($type as $name)
		{
			$this->addDataType($name, kDataTypeString, __NAMESPACE__ . '\\PostgreSQLStringData');
		}
		
		$type = array (
				'text' 
		);
		foreach ($type as $name)
		{
			$this->addDataType($name, kDataTypeString, __NAMESPACE__ . '\\PostgreSQLStringData');
		}
		
		$type = array (
				'bytes' 
		);
		foreach ($type as $name)
		{
			$this->addDataType($name, kDataTypeBinary, __NAMESPACE__ . '\\PostgreSQLBinaryData');
		}
		
		$type = array (
				'timestamp',
				'timestamp without timezone',
				'date',
				'time',
				'time without timezone' 
		);
		foreach ($type as $name)
		{
			$this->addDataType($name, kDataTypeTimestamp);
		}
		
		$this->addDataType("boolean", kDataTypeBoolean);
		
		// number types
		$type = array (
				'smallint',
				'integer',
				'bigint',
				'int2',
				'int4',
				'int8',
				'serial',
				'big serial' 
		);
		foreach ($type as $name)
		{
			$this->addDataType($name, kDataTypeNumber);
		}
		
		$type = array (
				'numeric',
				'decimal',
				'real',
				'double precision' 
		);
		foreach ($type as $name)
		{
			$this->addDataType($name, kDataTypeNumber);
		}
		
		$this->setDefaultTypeName(kDataTypeBinary, 'bytes', 'PostgreSQLBinaryData');
		$this->setDefaultTypeName(kDataTypeBoolean, 'boolean');
		$this->setDefaultTypeName(kDataTypeTimestamp, 'timestamp');
		$this->setDefaultTypeName(kDataTypeNumber, 'integer');
		$this->setDefaultTypeName(kDataTypeString, 'text');
	}

	public function __destruct()
	{
		parent::__destruct();
		if (is_resource($this->m_datasourceResource))
		{
			pg_close($this->m_datasourceResource);
		}
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
		 * $oQuery = new FormattedQuery($this, "COMMIT;");
		 * $oQuery->execute();
		 */
	}

	/**
	 *
	 * @see sources/sql/ITransactionBlock#rollbackTransaction()
	 */
	function rollbackTransaction()
	{
		/*
		 * $oQuery = new FormattedQuery($this, "ROLLBACK;");
		 * $oQuery->execute();
		 */
	}
	
	// ITableSetProvider implementation
	public function setActiveTableSet($name)
	{
		if ($name != $this->activeTableSetName)
		{
			$n = new PostgreSQLStringData($this);
			$n->import($name);
			$result = $this->executeQuery('SELECT count (*) FROM "pg_catalog"."pg_namespace" WHERE "nspname"=' . $n->expressionString());
			if (($result instanceof Recordset) && ($result->rowCount()))
			{
				$c = $result->current();
				if (intval($c[0]) == 0)
				{
					$this->executeQuery('CREATE SCHEMA ' . $n->expressionString());
				}
			}
		}
		
		$this->activeTableSetName = $name;
		
		return true;
	}
	
	// ITableProvider implementation
	

	/**
	 *
	 * @todo Check behavior
	 */
	public function getTable($a_strName, $a_strAlias = null, $a_strClassName = null, $useAliasAsName = false)
	{
		$schema = $this->getTableSet ($this->activeTableSetName);
		$res = tableProviderGenericTableObjectMethod($schema, $schema->structure->offsetGet ($a_strName), $a_strName, $a_strAlias, $a_strClassName, $useAliasAsName);
		return $res;
	}

	/**
	 *
	 * @see \NoreSources\SQL\ITableProvider::tableIterator()
	 */
	public function tableIterator()
	{
		$subStructure = null;
		if ($this->structure)
		{
			return $this->structure->offsetGet($this->activeTableSetName);
		}
		
		return null;
	}

	public function tableExists($a_strName, $a_mode = kObjectQuerySchema)
	{
		$result = true;
		if ($a_mode & kObjectQuerySchema)
		{
			$result = false;
			if ($this->structure)
			{
				$schema = $this->structure->offsetGet($this->activeTableSetName);
				if ($schema)
				{
					$result = $schema->offsetExists($a_strName);
				}
			}
		}
		
		if ($a_mode & kObjectQueryDatasource)
		{
			$a = $this->getTableSetStructure($this, false);
			$result = ($result && (($a instanceof TableSetStructure) && $a->offsetExists($a_strName) && ($a[$a_strName] instanceof TableStructure)));
		}
		
		return $result;
	}

	public function getDatasource()
	{
		return $this;
	}

	public function getTableSetStructure(SQLObject $a_containerObject, $recursive = false)
	{
		$schemaName = $this->activeTableSetName;
		if (is_object($a_containerObject) && ($a_containerObject instanceof TableSet))
		{
			$schemaName = $a_containerObject->getName();
		}
		
		$n = new PostgreSQLStringData($this);
		$n->import($schemaName);
		$s = 'SELECT table_name FROM "information_schema"."tables" where "table_schema"=' . $n->expressionString();
		$s = new FormattedQuery($this, $s);
		$records = $s->execute();
		if (!(is_object($records) && ($records instanceof Recordset)))
		{
			return false;
		}
		
		$structure = new TableSetStructure($this->structure, $schemaName);
		
		foreach ($records as $row)
		{
			$ts = null;
			if ($recursive)
			{
				$ts = $this->getTableStructure($this->getTable($row['table_name']));
			}
			else
			{
				$ts = new TableStructure($structure, $row['table_name']);
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
		return Reporter::fatalError($this, __METHOD__ . ' not imp', __FILE__, __LINE__);
	}
	
	// Datasource implementation
	

	/**
	 * Connection
	 *
	 * @param array $parameters parameters
	 * @return boolean
	 */
	public function connect($parameters)
	{
		if ($this->resource())
		{
			$this->disconnect();
		}
		
		if (!(array_key_exists(kConnectionParameterHostname, $parameters) && array_key_exists(kConnectionParameterUsername, $parameters)))
		{
			return ns\Reporter::instance()->addError($this, __METHOD__ . "(): Parameters are missing. 'host', 'user', ['password' and 'Database'] must be provided.", __FILE__, __LINE__);
		}
		
		$connectionString = "host = '" . $parameters[kConnectionParameterHostname] . "'";
		$connectionString .= " user = '" . $parameters[kConnectionParameterUsername] . "'";
		if (array_key_exists(kConnectionParameterPassword, $parameters))
		{
			$connectionString .= " password = '" . $parameters[kConnectionParameterPassword] . "'";
		}
		
		if (array_key_exists(kConnectionParameterDatabasename, $parameters))
		{
			$connectionString .= " dbname = '" . $parameters[kConnectionParameterDatabasename] . "'";
		}
		
		if (function_exists("pg_connect"))
		{
			$this->m_datasourceResource = pg_connect($connectionString);
		}
		else
		{
			return ns\Reporter::instance()->addError($this, __METHOD__ . "(): PostgreSQL extension is not installed", __FILE__, __LINE__);
		}
		
		if (!$this->m_datasourceResource)
		{
			return ns\Reporter::instance()->addError($this, __METHOD__ . "(): Unable to connect to data source " . $parameters[kConnectionParameterHostname], __FILE__, __LINE__);
		}
		
		if (array_key_exists(kConnectionParameterActiveTableSet, $parameters))
		{
			$this->setActiveTableSet($parameters[kConnectionParameterActiveTableSet]);
		}
		
		return true;
	}

	/**
	 *
	 * @see sources/sql/Datasource#disconnect()
	 * @return bool
	 */
	protected function disconnect()
	{
		return @pg_close($this->resource());
	}

	public function createData($dataType)
	{
		if (array_key_exists($dataType, $this->m_dataTypeNames))
		{
			$a = $this->m_dataTypeNames[$dataType];
			$sqlType = $a['type'];
			
			$d = null;
			if ($a['class'])
			{
				$cls = $a['class'];
				return (new $cls($this));
			}
		}
		
		if ($sqlType = $this->guessDataType($dataType))
		{
			if ($sqlType == kDataTypeString)
			{
				return (new PostgreSQLStringData($this));
			}
			elseif ($sqlType == kDataTypeBinary)
			{
				return (new PostgreSQLBinaryData($this));
			}
		}
		
		return parent::createData($dataType);
	}

	/**
	 *
	 * @see sources/sql/Datasource#executeQuery()
	 * @return QueryResult
	 */
	public function executeQuery($a_strQuery)
	{		
		$result = @pg_query($this->resource(), $a_strQuery);
		if ($result === false)
		{
			return ns\Reporter::error($this, __METHOD__ . "(): Query error: " . $a_strQuery . " / " . pg_last_error($this->resource()), __FILE__, __LINE__);
		}
		
		return $result;
	}

	/**
	 *
	 * @see sources/sql/Datasource#lastInsertId()
	 * @return integer
	 */
	public function lastInsertId()
	{
		/**
		 *
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
	 * @return array
	 */
	public function fetchResult(QueryResult $a_queryResult, $fetchFlags = kRecordsetFetchBoth)
	{
		$pgsqlFlags = 0;
		if ($fetchFlags & kRecordsetFetchName) $pgsqlFlags |= PGSQL_ASSOC;
		if ($fetchFlags & kRecordsetFetchNumeric) $pgsqlFlags |= PGSQL_NUM;
		
		return pg_fetch_array($a_queryResult->resultResource, null, $pgsqlFlags);
	}

	public function resetResult(QueryResult $a_queryResult)
	{
		$r = $a_queryResult->resultResource;
		return pg_result_seek($r, 0);
	}

	/**
	 *
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
	 *
	 * @see sources/sql/Datasource#resultRowCount()
	 * @return integer
	 */
	public function resultRowCount(QueryResult $a_queryResult)
	{
		return pg_num_rows($a_queryResult->resultResource);
	}

	/**
	 *
	 * @see sources/sql/Datasource#recordsetColumnArray()
	 * @return array
	 */
	public function recordsetColumnArray(QueryResult $a_queryResult)
	{
		$res = array ();
		$n = pg_num_fields($a_queryResult->resultResource);
		for ($i = 0; $i < $n; $i++)
		{
			$res[] = pg_field_name($a_queryResult->resultResource, $i);
		}
		return $res;
	}

	/**
	 *
	 * @see sources/sql/Datasource#affectedRowCount()
	 * @return integer
	 */
	public function affectedRowCount(QueryResult $a_queryResult)
	{
		return pg_affected_rows($a_queryResult->datasource->resource());
	}

	/**
	 *
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

	/**
	 *
	 * @return bool
	 */
	public function caseSensitiveTableNames()
	{
		return true;
	}

	protected $activeTableSetName;
}
