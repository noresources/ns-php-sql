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

require_once (__DIR__ . '/../base.php');
require_once (NS_PHP_CORE_PATH . '/arrays.php');

class MySQLTableManipulator extends TableManipulator
{

	/**
	 *
	 * @param ITableProvider $provider
	 */
	public function __construct(ITableProvider $provider = null)
	{
		parent::__construct($provider);
	}

	/**
	 *
	 * @param TableStructure $structure
	 */
	public function create(TableStructure $structure)
	{
		$t = new Table($this->m_provider, $structure->getName());
		$strQuery = 'CREATE TABLE ' . $t->expressionString() . ' (';
		
		$first = true;
		$primaryKeyColumns = array ();
		
		foreach ($structure as $name => $column)
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
				$length = $column->getProperty(kStructureDataSize);
				$decimals = $column->getProperty(kStructureDecimalCount);
				if ($length)
				{
					$strQuery .= '(' . $length;
					if ($decimals)
					{
						$strQuery .= ', ' . $decimals;
					}
					$strQuery .= ')';
				}
			}
			
			if (!$acceptNull)
			{
				$strQuery .= ' NOT NULL';
			}
			
			if ($auto)
			{
				$strQuery .= ' AUTO_INCREMENT';
			}
			
			if ($column->getProperty(FIELD_PRIMARYKEY))
			{
				$primaryKeyColumns[] = $this->m_datasource->encloseElement($column->getName());
				;
			}
		}
		
		$strQuery .= ')';
		
		if (count($primaryKeyColumns))
		{
			$strQuery . ' PRIMARY KEY (' . implode(', ', $primaryKeyColumns) . ')';
		}
		
		$q = new FormattedQuery($this->m_provider->datasource, $strQuery);
		
		return $q->execute();
	}
}

/**
 * MySQL implementation using 'mysql' PHP extension
 *
 * @var integer
 */
const kMySQLImplementationMysql = 1;

/**
 * MySQL implementation using 'mysqli' PHP extension
 *
 * @var integer
 */
const kMySQLImplementationMysqli = 2;

/**
 */
class MySQLDatasource extends Datasource implements ITransactionBlock
{

	// construction - destruction
	
	/**
	 *
	 * @param DatasourceStructure $structure
	 */
	public function __construct(DatasourceStructure $structure = null)
	{
		parent::__construct($structure);
		$this->setDatasourceString(self::kStringClassNameTableManipulator, __NAMESPACE__ . '\\MySQLTableManipulator');
		$this->setDatasourceString(self::kStringImplementationTypeKey, basename(__DIR__));
		
		$this->m_implementation = 0;
		if (extension_loaded('mysqli'))
		{
			$this->m_implementation = kMySQLImplementationMysqli;
		}
		elseif (extension_loaded('mysql'))
		{
			$this->m_implementation = kMySQLImplementationMysql;
		}
		
		// text types
		$this->addDataType('TEXT', kDataTypeString);
		$types = array (
				'CHAR',
				'VARCHAR',
				'TINYTEXT',
				'TINYBLOB',
				'BLOB',
				'MEDIUMTEXT',
				'MEDIUMBLOB',
				'LONGTEXT',
				'LONGBLOB',
				'ENUM' 
		);
		foreach ($types as $name)
		{
			$this->addDataType($name, kDataTypeString);
		}
		
		$this->addDataType('BINARY', kDataTypeBinary);
		$this->addDataType('VARBINARY', kDataTypeBinary);
		
		$this->addDataType('BOOL', kDataTypeBoolean);
		
		$this->addDataType('TIMESTAMP', kDataTypeTimestamp);
		$this->addDataType('DATE', kDataTypeTimestamp);
		$this->addDataType('TIME', kDataTypeTimestamp);
		$this->addDataType('DATETIME', kDataTypeTimestamp);
		
		// number types
		$types = array (
				'INT',
				'BIGINT',
				'MEDIUMINT',
				'SMALLINT',
				'TINYINT',
				//'INT',
				'FLOAT',
				'DOUBLE',
				'DECIMAL' 
		);
		foreach ($types as $name)
		{
			$this->addDataType($name, kDataTypeNumber);
		}
	}

	/**
	 */
	public function __destruct()
	{
		parent::__destruct();
		if (is_resource($this->resource) && !($this->flags & kConnectionPersistent))
		{
			$this->apiCall('close', $this->resource);
		}
	}

	// ITransactionBlock implementation
	
	/**
	 *
	 * @see sources/sql/ITransactionBlock#startTransaction()
	 */
	function startTransaction()
	{
		$oQuery = new FormattedQuery($this, 'START TRANSACTION;');
		$oQuery->execute();
	}

	/**
	 *
	 * @see sources/sql/ITransactionBlock#commitTransaction()
	 */
	function commitTransaction()
	{
		$oQuery = new FormattedQuery($this, 'COMMIT;');
		$oQuery->execute();
	}

	/**
	 *
	 * @see sources/sql/ITransactionBlock#rollbackTransaction()
	 */
	function rollbackTransaction()
	{
		$oQuery = new FormattedQuery($this, 'ROLLBACK;');
		$oQuery->execute();
	}

	// ITableSetProvider
	public function setActiveTableSet($name)
	{
		$res = null;
		if ($this->m_implementation == kMySQLImplementationMysqli)
		{
			$res = $this->apiCall('select_db', $this->resource, $name);
		}
		elseif ($this->m_implementation == kMySQLImplementationMysql)
		{
			$res = $this->apiCall('select_db', $name, $this->resource);
		}
		
		return ($res != false);
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
		
		if (!array_key_exists(kConnectionParameterPassword, $parameters))
		{
			$parameters[kConnectionParameterPassword] = '';
		}
		
		if (!(array_key_exists(kConnectionParameterHostname, $parameters) && array_key_exists(kConnectionParameterUsername, $parameters)))
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Missing parameters "host"', __FILE__, __LINE__);
		}
		
		$host = $parameters[kConnectionParameterHostname];
		$user = ns\array_keyvalue($parameters, kConnectionParameterUsername, null);
		$pass = ns\array_keyvalue($parameters, kConnectionParameterPassword, null);
		
		$connectionFunction = 'connect';
		if (ns\array_keyvalue($parameters, kConnectionParameterPersistent, false))
		{
			$this->setDatasourceFlags($this->flags | kConnectionPersistent);
			if ($this->m_implementation == kMySQLImplementationMysqli)
			{
				$host = 'p:' . $host;
			}
			else
			{
				$connectionFunction = 'pconnect';
			}
		}
		
		$this->m_datasourceResource = $this->apiCall($connectionFunction, $host, $user, $pass);
		
		if (!$this->resource)
		{
			$message = 'Unable to connect to MySQL Datasource ';
			if ($user)
			{
				$message .= $user . '@';
			}
			$message .= $host;
			if ($pass)
			{
				$message .= ' with password';
			}
			
			$message .= "\n" . $this->apiCall('error', $this->resource);
			
			return ns\Reporter::error($this, __METHOD__ . '(): ' . $message, __FILE__, __LINE__);
		}
		
		$activeTableSet = null;
		
		// Legacy behavior
		if (array_key_exists(kConnectionParameterDatabasename, $parameters))
		{
			$activeTableSet = $parameters[kConnectionParameterDatabasename];
		}
		
		if (array_key_exists(kConnectionParameterActiveTableSet, $parameters))
		{
			$activeTableSet = $parameters[kConnectionParameterActiveTableSet];
		}
		
		if (is_string($activeTableSet))
		{
			$res = $this->setActiveTableSet($activeTableSet);
			
			if (!$res)
			{
				return ns\Reporter::error($this, __METHOD__ . '(): ' . $this->apiCall('error', $this->resource));
			}
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
		return @$this->apiCall('close', $this->resource);
	}

	public function serializeStringData($stringData)
	{
		return ($this->datasource->apiCall("real_escape_string", $stringData, $this->resource));
	}

	public function serializeBinaryData($data)
	{
		return "X'" . bin2hex($data) . "'";
	}

	/**
	 * MySQL API automatically unserialize binary data on SELECT
	 */
	public function unserializeBinaryData($data)
	{
		return $data;
	}

	/**
	 *
	 * @see sources/sql/Datasource#executeQuery()
	 * @return QueryResult
	 */
	public function executeQuery($query)
	{
		$result = $this->apiCall('query', $query, $this->resource);
		if ($result === false)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Query error: ' . $query . ' / ' . $this->apiCall('error', $this->resource));
		}
		
		return $result;
	}

	/**
	 *
	 * @return integer
	 */
	public function getLastInsertId(QueryResult $result = null)
	{
		return $this->apiCall('insert_id', $this->resource);
	}

	/**
	 *
	 * @return
	 */
	public function fetchResult(QueryResult $result, $fetchFlags = kRecordsetFetchBoth)
	{
		$mysqlFlags = 0;
		if ($this->m_implementation == kMySQLImplementationMysql)
		{
			if ($fetchFlags & kRecordsetFetchName)
				$mysqlFlags |= MYSQL_ASSOC;
			if ($fetchFlags & kRecordsetFetchNumeric)
				$mysqlFlags |= MYSQL_NUM;
		}
		elseif ($this->m_implementation == kMySQLImplementationMysqli)
		{
			if ($fetchFlags & kRecordsetFetchName)
				$mysqlFlags |= MYSQLI_ASSOC;
			if ($fetchFlags & kRecordsetFetchNumeric)
				$mysqlFlags |= MYSQLI_NUM;
		}
		
		return $this->apiCall('fetch_array', $result->resultResource, $mysqlFlags);
	}

	public function resetResult(QueryResult $result)
	{
		$r = $result->resultResource;
		if ($this->isValidResult($result))
		{
			return $this->apiCall('data_seek', $r, 0);
		}
		else
		{
			$t = gettype($r);
			if (is_object($r))
			{
				$t = get_class($r);
			}
			return ns\Reporter::error($this, __METHOD__ . '(): Invalid result object (' . $t . ')', __FILE__, __LINE__);
		}
	}

	/**
	 *
	 * @see sources/sql/Datasource#freeResult()
	 */
	public function freeResult(QueryResult $result)
	{
		if ($this->isValidResult($result))
		{
			return $this->apiCall('free_result', $result->resultResource);
		}
		
		return false;
	}

	/**
	 *
	 * @see sources/sql/Datasource#resultRowCount()
	 * @return integer
	 */
	public function resultRowCount(QueryResult $result)
	{
		return $this->apiCall('num_rows', $result->resultResource);
	}

	/**
	 *
	 * @see sources/sql/Datasource#recordsetColumnArray()
	 * @return array
	 */
	public function recordsetColumnArray(QueryResult $result)
	{
		$res = array ();
		$n = $this->apiCall('num_fields', $result->resultResource);
		for ($i = 0; $i < $n; $i++)
		{
			$res[] = $this->apiCall('field_name', $result->resultResource, $i);
		}
		return $res;
	}

	/**
	 *
	 * @return integer
	 */
	public function getAffectedRowCount(QueryResult $result)
	{
		return $this->apiCall('affected_rows', $result->datasource->resource());
	}

	/**
	 *
	 * @see sources/sql/Datasource#encloseElement()
	 * @return string
	 */
	public function encloseElement($element)
	{
		return ($element == '*') ? $element : '`' . $element . '`';
	}

	/**
	 *
	 * @return TableSetStructure
	 */
	public function getTableSetStructure(SQLObject $containerObject, $recursive = false)
	{
		/**
		 *
		 * @todo support Datasource as argument if a db is selected
		 */
		if (!($containerObject instanceof TableSet))
		{
			return ns\Reporter::fatalError($this, __METHOD__ . '(): TableSet class required');
		}
		
		$query = new FormattedQuery($this, 'SHOW TABLES FROM ' . $containerObject->expressionString(kExpressionElementName));
		$queryRes = $query->execute();
		if ($queryRes === false)
		{
			return false;
		}
		
		$p = $containerObject->structure ? $containerObject->structure->parent() : null;
		$structure = new TableSetStructure($p, $containerObject->getName());
		foreach ($queryRes as $row)
		{
			$ts = null;
			if ($recursive)
			{
				$ts = $this->getTableStructure($this->getTable($row[0]));
			}
			else
			{
				$ts = new TableStructure($structure, $row[0]);
			}
			
			if ($ts)
			{
				$structure->addTableStructure($ts);
			}
		}
		
		return $structure;
	}

	/**
	 *
	 * @see sources/sql/Datasource#fieldArray()
	 * @deprecated : Use xml structure
	 * @return array
	 */
	public function getTableStructure(Table $table)
	{
		$query = new FormattedQuery($this, 'SHOW COLUMNS FROM ' . $table->expressionString(kExpressionElementName));
		$queryRes = $query->execute();
		if ($queryRes === false)
		{
			return $queryRes;
		}
		
		$s = $table->structure;
		$ts = new TableStructure(($s ? $s->parent() : null), $table->getName());
		
		foreach ($queryRes as $row)
		{
			$name = $row['Field'];
			$elements = null;
			$typedef = null;
			
			if ($type = $this->parseListedTypeValue($row['Type'], $elements))
			{
				$typedef = array (
						'type' => $type,
						'size' => false,
						'dec_size' => false,
						kStructureAcceptMultipleValues => false 
				);
				if ($type == 'enum')
				{
					$typedef[kStructureValidatorClassname] = 'MySQLEnumColumnValueValidator';
				}
				elseif ($type == 'set')
				{
					$typedef[kStructureValidatorClassname] = 'MySQLSetColumnValueValidator';
					$typedef[kStructureAcceptMultipleValues] = true;
				}
			}
			else
			{
				$typedef = parseDataTypeDefinition($row['Type'], true);
			}
			$f = new TableColumnStructure($ts, $name);
			
			$f->setProperty(kStructurePrimaryKey, preg_match('/pri/i', $row['Key']));
			$f->setProperty(kStructureAutoincrement, preg_match('/auto_increment/i', $row['Extra']));
			$f->setProperty(kStructureAcceptNull, preg_match('/yes/i', $row['Null']));
			$f->setProperty(kStructureColumnTypename, $typedef['type']);
			$f->setProperty(kStructureValidatorClassname, ns\array_keyvalue($typedef, kStructureValidatorClassname, false));
			$f->setProperty(kStructureAcceptMultipleValues, $typedef[kStructureAcceptMultipleValues]);
			
			if ($elements)
			{
				$f->setProperty(kStructureDatatype, $type);
				$f->setProperty(kStructureEnumeration, $elements);
			}
			
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

	protected function parseListedTypeValue($type, $elements)
	{
		$regs = array ();
		$elements = array ();
		
		if (!preg_match('/(enum|set)\((.*)\)/i', $type, $regs))
		{
			return false;
		}
		
		$type = strtolower(trim($regs[1]));
		
		mb_ereg_search_init($regs[2], '\'(.*?)\'');
		while ($regs = mb_ereg_search_regs())
		{
			$elements[] = $regs[1];
		}
		return $type;
	}

	public function implementation()
	{
		return $this->m_implementation;
	}

	public function isValidResult(QueryResult $result)
	{
		$r = $result->resultResource;
		if ($this->m_implementation == kMySQLImplementationMysqli)
		{
			return ($r instanceof \mysqli_result);
		}
		elseif ($this->m_implementation == kMySQLImplementationMysql)
		{
			return is_resource($r);
		}
		
		return false;
	}

	public function apiCall($functionName /*, ...*/)
	{
		$prefix = 'mysql_';
		if ($this->m_implementation == kMySQLImplementationMysqli)
		{
			$prefix = 'mysqli_';
		}
		
		$f = $prefix . $functionName;
		
		$startIndex = 1;
		$endIndex = func_num_args();
		
		$args = array ();
		
		// Auto reverse args if mysqli appears at end of arg list
		if ($this->m_implementation == kMySQLImplementationMysqli)
		{
			if ($endIndex > 1)
			{
				$lastArg = func_get_arg($endIndex - 1);
				if (($lastArg instanceof \mysqli))
				{
					$args[] = $lastArg;
					$endIndex--;
				}
			}
		}
		
		for ($i = $startIndex; $i < $endIndex; $i++)
		{
			$args[] = func_get_arg($i);
		}
		
		return call_user_func_array($f, $args);
	}

	protected $m_implementation;
}
