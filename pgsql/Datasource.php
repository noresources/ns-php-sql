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
		$this->setDatasourceString(self::kStringImplementationTypeKey, basename(__DIR__));
		
		$this->activeTableSetName = self::kDefaultTableSetName;
		
		// Keywords
		$this->setDatasourceString(self::kStringKeywordTrue, "TRUE");
		$this->setDatasourceString(self::kStringKeywordFalse, "FALSE");
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
		$oQuery = new FormattedQuery($this, 'BEGIN');
		$oQuery->execute();
	}

	/**
	 *
	 * @see sources/sql/ITransactionBlock#commitTransaction()
	 */
	function commitTransaction()
	{
		$oQuery = new FormattedQuery($this, 'COMMIT');
		$oQuery->execute();
	}

	/**
	 *
	 * @see sources/sql/ITransactionBlock#rollbackTransaction()
	 */
	function rollbackTransaction()
	{
		$oQuery = new FormattedQuery($this, 'ROLLBACK');
		$oQuery->execute();
	}

	public function getDefaultTableSet()
	{
		return self::kDefaultTableSetName;	
	}
	
	public function getActiveTableSet()
	{
		return $this->activeTableSetName;
	}
	
	// ITableSetProvider implementation
	public function setActiveTableSet($name)
	{
		if ($name != $this->activeTableSetName)
		{
			$n = new StringData($this);
			$n->import($name);
			$result = $this->executeQuery('SELECT count (*) FROM "pg_catalog"."pg_namespace" WHERE "nspname"=' . $n->expressionString());
			if (($result instanceof Recordset) && ($result->rowCount))
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
		$schema = $this->getTableSet($this->activeTableSetName);
		$res = tableProviderGenericTableObjectMethod($schema, $schema->structure->offsetGet($a_strName), $a_strName, $a_strAlias, $a_strClassName, $useAliasAsName);
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
		
		$n = new StringData($this);
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
		return ns\Reporter::fatalError($this, __METHOD__ . ' not imp', __FILE__, __LINE__);
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
		if ($this->resource)
		{
			$this->disconnect();
		}
		
		if (!(array_key_exists(kConnectionParameterHostname, $parameters) && array_key_exists(kConnectionParameterUsername, $parameters)))
		{
			return ns\Reporter::error($this, __METHOD__ . "(): Parameters are missing. 'host', 'user', ['password' and 'Database'] must be provided.", __FILE__, __LINE__);
		}
		
		$connectionString = "host = '" . $parameters[kConnectionParameterHostname] . "'";
		if (array_key_exists(kConnectionParameterPort, $parameters))
		{
			$connectionString .= " port = " . intval($parameters[kConnectionParameterPort]);
		}
		$connectionString .= " user = '" . $parameters[kConnectionParameterUsername] . "'";
		if (array_key_exists(kConnectionParameterPassword, $parameters))
		{
			$connectionString .= " password = '" . $parameters[kConnectionParameterPassword] . "'";
		}
		
		$database = array_key_exists(kConnectionParameterDatabasename, $parameters) ? $parameters[kConnectionParameterDatabasename] : $parameters[kConnectionParameterUsername];
		$connectionString .= " dbname = '" . $database . "'";
		
		if (function_exists("pg_connect"))
		{
			$this->m_datasourceResource = pg_connect($connectionString);
		}
		else
		{
			return ns\Reporter::error($this, __METHOD__ . "(): PostgreSQL extension is not installed", __FILE__, __LINE__);
		}
		
		if (!$this->m_datasourceResource)
		{
			return ns\Reporter::error($this, __METHOD__ . "(): Unable to connect to data source " . $parameters[kConnectionParameterHostname], __FILE__, __LINE__);
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
		return @pg_close($this->resource);
	}

	public function createData($dataType)
	{
		$sqlType = self::guessDataType($dataType);
		if ($sqlType === false)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Unable to find type', __FILE__, __LINE__);
		}
		
		if ($sqlType == kDataTypeBinary)
		{
			return new PostgreSQLBinaryData($this);
		}
		
		return parent::createData($dataType);
	}

	public function serializeStringData($stringData)
	{
		return (pg_escape_string($this->resource, $stringData));
	}

	public function serializeBinaryData($data)
	{
		return (pg_escape_bytea($this->resource, $data));
	}

	public function unserializeBinaryData($data)
	{
		return pg_unescape_bytea($data);
	}

	/**
	 *
	 * @see sources/sql/Datasource#executeQuery()
	 * @return QueryResult
	 */
	public function executeQuery($a_strQuery)
	{
		$result = @pg_query($this->resource, $a_strQuery);
		if ($result === false)
		{
			return ns\Reporter::error($this, __METHOD__ . "(): Query error: " . $a_strQuery . " / " . pg_last_error($this->resource), __FILE__, __LINE__);
		}
		
		return $result;
	}

	/**
	 *
	 * @return integer
	 *
	 * @see http://www.php.net/manual/fr/function.pg-last-oid.php
	 * @see https://stackoverflow.com/questions/2741919/can-i-ask-postgresql-to-ignore-errors-within-a-transaction
	 * @see https://wiki.postgresql.org/wiki/Transactions_recovering_failures_in_scripts
	 */
	public function getLastInsertId(QueryResult $a_queryResult = null)
	{
		$id = null;
		$savePointKey = '_NS_PHP_SQL_LASTVAL_SAVEPOINT_';
		$transaction = (pg_transaction_status($this->resource) !== PGSQL_TRANSACTION_IDLE);
		
		if ($transaction)
			@pg_query ('SAVEPOINT ' . $savePointKey);
		$result = @pg_query('SELECT LASTVAL()');
		if (pg_result_status($result, PGSQL_TUPLES_OK))
		{
			$id = intval (pg_fetch_result($result, 0, 0));
		}
		if ($transaction)
		{
			@pg_query ('RELEASE ' . $savePointKey);
			@pg_query ('ROLLBACK TO SAVEPOINT ' . $savePointKey);
		}
				
		return $id;
	}

	/**
	 *
	 * @return array
	 */
	public function fetchResult(QueryResult $a_queryResult, $fetchFlags = kRecordsetFetchBoth)
	{
		$resource = $a_queryResult->resultResource;
		
		$names = array ();
		$numbers = pg_fetch_array($resource, null, PGSQL_NUM);
		if (!\is_array($numbers))
			return $numbers;
		
		foreach ($numbers as $index => &$value)
		{
			$oid = pg_field_type_oid($resource, $index);
			switch ($oid)
			{
				case 16: // boolean
				{
						$value = \in_array($value, array (
								TRUE,
								't',
								'true',
								'y',
								'yes',
								'on',
								'1' 
						), true);
					}
					break;
				case 17: // bytea
				{
					$value = pg_unescape_bytea($value);
					}
					break;
			}
						
			if ($fetchFlags & kRecordsetFetchName)
			{
				if ($fetchFlags & kRecordsetFetchName)
				{
					$names[pg_field_name($resource, $index)] = $value;
				}
			}
		}
		
		if ($fetchFlags & kRecordsetFetchName)
		{
			if ($fetchFlags & kRecordsetFetchNumeric)
			{
				return array_merge($numbers, $names);
			}
			
			return $names;
		}
		
		return $numbers;		
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
	 * @return integer
	 */
	public function getAffectedRowCount(QueryResult $a_queryResult)
	{
		return pg_affected_rows($a_queryResult->resultResource);
	}

	/**
	 *
	 * @see sources/sql/Datasource#encloseElement()
	 * @return string
	 */
	public function encloseElement($element)
	{
		if ($element != "*")
		{
			if (function_exists("pg_escape_identifier"))
			{
				return pg_escape_identifier($this->resource, $element);
			}
			return '"' . $element . '"';
		}
		
		return $element;
	}

	protected $activeTableSetName;

	public static function initialize()
	{
		if (!self::initializeDatasourceData(get_called_class()))
			return;
		
		// Data types
		
		$type = array (
				'character varying',
				'varchar',
				'text' 
		);
		
		foreach ($type as $name)
		{
			self::addDataType($name, kDataTypeString);
		}
		
		$type = array (
				'bytes' 
		);
		foreach ($type as $name)
		{
			self::addDataType($name, kDataTypeBinary, __NAMESPACE__ . '\\PostgreSQLBinaryData');
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
			self::addDataType($name, kDataTypeTimestamp);
		}
		
		self::addDataType("boolean", kDataTypeBoolean);
		
		$type = array (
				'numeric',
				'decimal',
				'real',
				'double precision' 
		);
		foreach ($type as $name)
		{
			self::addDataType($name, kDataTypeDecimal);
			self::addDataType($name, kDataTypeNumber);
		}
		
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
			self::addDataType($name, kDataTypeInteger);
			self::addDataType($name, kDataTypeNumber);
		}
		
		self::setDefaultTypeName(kDataTypeBinary, 'bytes', PostgreSQLBinaryData::class);
		self::setDefaultTypeName(kDataTypeBoolean, 'boolean');
		self::setDefaultTypeName(kDataTypeTimestamp, 'timestamp');
		self::setDefaultTypeName(kDataTypeInteger, 'integer');
		self::setDefaultTypeName(kDataTypeDecimal, 'real');
		self::setDefaultTypeName(kDataTypeNumber, 'numeric');
		self::setDefaultTypeName(kDataTypeString, 'text');
	}
}

PostgreSQLDatasource::initialize();
