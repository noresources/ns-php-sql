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

require_once (__DIR__ . '/../base.php');
require_once (NS_PHP_CORE_PATH . '/arrays.php');

class MySQLTableManipulator extends TableManipulator
{

	/**
	 *
	 * @param ITableProvider $a_oProvider
	 */
	public function __construct(ITableProvider $a_oProvider = null)
	{
		parent::__construct($a_oProvider);
	}

	/**
	 *
	 * @param TableStructure $a_structure
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
		$primaryKeyFields = array ();
		
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
			if (!is_null($type))
			{
				$strQuery .= ' ' . $this->m_datasource->getDefaultTypeName($type);
				$length = $field->getProperty(kStructureDataSize);
				$decimals = $field->getProperty(kStructureDecimalCount);
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
			
			if ($field->getProperty(FIELD_PRIMARYKEY))
			{
				$primaryKeyFields [] = $this->m_datasource->encloseElement($field->getName());
				;
			}
		}
		
		$strQuery .= ')';
		
		if (count($primaryKeyFields))
		{
			$strQuery . ' PRIMARY KEY (' . implode(', ', $primaryKeyFields) . ')';
		}
		
		$q = new FormattedQuery($this->m_provider->datasource, $strQuery);
		
		return $q->execute();
	}
}

/**
 */
class MySQLDatasource extends Datasource implements ITransactionBlock
{
	// construction - destruction
	

	/**
	 * MySQL implementation using 'mysql' PHP extension
	 *
	 * @var integer
	 */
	const kMySQExtension_mysql = 1;
	
	/**
	 * MySQL implementation using 'mysqli' PHP extension
	 *
	 * @var integer
	 */
	const kMySQExtension_mysqli = 2;

	/**
	 *
	 * @param DatasourceStructure $a_structure
	 */
	public function __construct(DatasourceStructure $a_structure = null)
	{
		parent::__construct($a_structure);
		$this->setDatasourceString(self::kStringClassNameTableManipulator, __NAMESPACE__ . '\\MySQLTableManipulator');
		
		$this->m_implementation = 0;
		if (extension_loaded('mysqli'))
		{
			$this->m_implementation = self::kMySQExtension_mysqli;
		}
		elseif (extension_loaded('mysql'))
		{
			$this->m_implementation = self::kMySQExtension_mysql;
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
			$this->addDataType($name, kDataTypeString, __NAMESPACE__ . '\\MySQLStringData');
		}
		
		$this->addDataType('BINARY', kDataTypeBinary, __NAMESPACE__ . '\\MySQLBinaryData');
		$this->addDataType('VARBINARY', kDataTypeBinary, __NAMESPACE__ . '\\MySQLBinaryData');
		
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
	
	// Datasource implementation
	

	/**
	 * Connection
	 *
	 * @param array $a_aParameters parameters
	 * @return boolean
	 */
	public function connect($a_aParameters)
	{
		if ($this->resource)
		{
			$this->disconnect();
		}
		
		if (!array_key_exists(kConnectionParameterPassword, $a_aParameters))
		{
			$a_aParameters [kConnectionParameterPassword] = '';
		}
		
		if (!(array_key_exists(kConnectionParameterHostname, $a_aParameters) && array_key_exists(kConnectionParameterUsername, $a_aParameters)
						/* already tested */
						/* && array_key_exists(kConnectionParameterPassword, $a_aParameters) */
						/* not required */
						/* && array_key_exists(kConnectionParameterDatabasename, $a_aParameters) */
				))
		{
			
			return ns\Reporter::error($this, __METHOD__ . '(): Missing parameters "host"', __FILE__, __LINE__);
		}
		
		$connectionFunction = 'connect';
		if (array_key_exists(kConnectionParameterPersistent, $a_aParameters) && $a_aParameters [kConnectionParameterPersistent])
		{
			$this->setDatasourceFlags($this->flags | kConnectionPersistent);
		}
		
		$host = $a_aParameters [kConnectionParameterHostname];
		$user = ns\array_keyvalue($a_aParameters, kConnectionParameterUsername, null);
		$pass = ns\array_keyvalue($a_aParameters, kConnectionParameterPassword, null);
		
		if ($this->flags & kConnectionPersistent)
		{
			if ($this->m_implementation == self::kMySQExtension_mysqli)
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
		
		if (array_key_exists(kConnectionParameterDatabasename, $a_aParameters))
		{
			$res = null;
			if ($this->m_implementation == self::kMySQExtension_mysqli)
			{
				$res = $this->apiCall('select_db', $this->resource, $a_aParameters [kConnectionParameterDatabasename]);
			}
			elseif ($this->m_implementation == self::kMySQExtension_mysql)
			{
				$res = $this->apiCall('select_db', $a_aParameters [kConnectionParameterDatabasename], $this->resource);
			}
			
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
				return (new MySQLStringData($this, $structure));
			}
			elseif ($sqlType == kDataTypeBinary)
			{
				return (new MySQLBinaryData($this, $structure));
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
		$result = $this->apiCall('query', $a_strQuery, $this->resource);
		if ($result === false)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Query error: ' . $a_strQuery . ' / ' . $this->apiCall('error', $this->resource));
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
		return $this->apiCall('insert_id', $this->resource);
	}

	/**
	 *
	 * @see sources/sql/Datasource#fetchResult()
	 * @return
	 *
	 *
	 */
	public function fetchResult(QueryResult $a_queryResult)
	{
		return $this->apiCall('fetch_array', $a_queryResult->resultResource);
	}

	public function resetResult(QueryResult $a_queryResult)
	{
		$r = $a_queryResult->resultResource;
		if ($this->isValidResult($a_queryResult))
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
	public function freeResult(QueryResult $a_queryResult)
	{
		if ($this->isValidResult($a_queryResult))
		{
			return $this->apiCall('free_result', $a_queryResult->resultResource);
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
		return $this->apiCall('num_rows', $a_queryResult->resultResource);
	}

	/**
	 *
	 * @see sources/sql/Datasource#recordsetColumnArray()
	 * @return array
	 */
	public function recordsetColumnArray(QueryResult $a_queryResult)
	{
		$res = array ();
		$n = $this->apiCall('num_fields', $a_queryResult->resultResource);
		for($i = 0; $i < $n; $i++)
		{
			$res [] = $this->apiCall('field_name', $a_queryResult->resultResource, $i);
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
		return $this->apiCall('affected_rows', $a_queryResult->datasource->resource());
	}

	/**
	 *
	 * @see sources/sql/Datasource#encloseElement()
	 * @return string
	 */
	public function encloseElement($a_strElement)
	{
		return ($a_strElement == '*') ? $a_strElement : '`' . $a_strElement . '`';
	}
	
	// Database relative
	

	// default behavior
	// public function getDatabaseIterator()
	// public function databaseExists($a_strDatabaseName)
	public function getDatabaseStructure(SQLObject $a_containerObject, $recursive = false)
	{
		/**
		 *
		 * @todo support Datasource as argument if a db is selected
		 */
		if (!($a_containerObject instanceof Database))
		{
			return $mm->fatalError($this, __METHOD__ . '(): Database class required');
		}
		
		$query = new FormattedQuery($this, 'SHOW TABLES FROM ' . $a_containerObject->expressionString(kExpressionElementName));
		$queryRes = $query->execute();
		if ($queryRes === false)
		{
			return false;
		}
		
		$p = $a_containerObject->structure ? $a_containerObject->structure->parent() : null;
		$structure = new DatabaseStructure($p, $a_containerObject->getName());
		foreach ($queryRes as $row)
		{
			$ts = null;
			if ($recursive)
			{
				$ts = $this->getTableStructure($this->getTable($row [0]));
			}
			else
			{
				$ts = new TableStructure($structure, $row [0]);
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
	public function getTableStructure(Table $a_table)
	{
		$query = new FormattedQuery($this, 'SHOW COLUMNS FROM ' . $a_table->expressionString(kExpressionElementName));
		$queryRes = $query->execute();
		if ($queryRes === false)
		{
			return $queryRes;
		}
		
		$s = $a_table->structure;
		$ts = new TableStructure(($s ? $s->parent() : null), $a_table->getName());
		
		foreach ($queryRes as $row)
		{
			$name = $row ['Field'];
			$elements = null;
			$typedef = null;
			
			if ($type = $this->parseListedTypeValue($row ['Type'], $elements))
			{
				$typedef = array (
						'type' => $type,
						'size' => false,
						'dec_size' => false,
						kStructureAcceptMultipleValues => false 
				);
				if ($type == 'enum')
				{
					$typedef [kStructureValidatorClassname] = 'MySQLEnumFieldValueValidator';
				}
				elseif ($type == 'set')
				{
					$typedef [kStructureValidatorClassname] = 'MySQLSetFieldValueValidator';
					$typedef [kStructureAcceptMultipleValues] = true;
				}
			}
			else
			{
				$typedef = parseDataTypeDefinition($row ['Type'], true);
			}
			$f = new TableFieldStructure($ts, $name);
			
			$f->setProperty(kStructurePrimaryKey, preg_match('/pri/i', $row ['Key']));
			$f->setProperty(kStructureAutoincrement, preg_match('/auto_increment/i', $row ['Extra']));
			$f->setProperty(kStructureAcceptNull, preg_match('/yes/i', $row ['Null']));
			$f->setProperty(kStructureFieldTypename, $typedef ['type']);
			$f->setProperty(kStructureValidatorClassname, ns\array_keyvalue($typedef, kStructureValidatorClassname, false));
			$f->setProperty(kStructureAcceptMultipleValues, $typedef [kStructureAcceptMultipleValues]);
			
			if ($elements)
			{
				$f->setProperty(kStructureDatatype, $type);
				$f->setProperty(kStructureEnumeration, $elements);
			}
			
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

	protected function parseListedTypeValue($type, $elements)
	{
		$regs = array ();
		$elements = array ();
		
		if (!preg_match('/(enum|set)\((.*)\)/i', $type, $regs))
		{
			return false;
		}
		
		$type = strtolower(trim($regs [1]));
		
		mb_ereg_search_init($regs [2], '\'(.*?)\'');
		while ($regs = mb_ereg_search_regs())
		{
			$elements [] = $regs [1];
		}
		return $type;
	}

	public function implementation()
	{
		return $this->m_implementation;
	}

	public function isValidResult(QueryResult $a_queryResult)
	{
		$r = $a_queryResult->resultResource;
		if ($this->m_implementation == self::kMySQExtension_mysqli)
		{
			return ($r instanceof \mysqli_result);
		}
		elseif ($this->m_implementation == self::kMySQExtension_mysql)
		{
			return is_resource($r);
		}
		
		return false;
	}

	public function apiCall($functionName /*, ...*/)
	{
		$prefix = 'mysql_';
		if ($this->m_implementation == self::kMySQExtension_mysqli)
		{
			$prefix = 'mysqli_';
		}
		
		$f = $prefix . $functionName;
		
		$startIndex = 1;
		$endIndex = func_num_args();
		
		$args = array ();
		
		// Auto reverse args if mysqli appears at end of arg list
		if ($this->m_implementation == self::kMySQExtension_mysqli)
		{
			if ($endIndex > 1)
			{
				$lastArg = func_get_arg($endIndex - 1);
				if (($lastArg instanceof \mysqli))
				{
					$args [] = $lastArg;
					$endIndex--;
				}
			}
		}
		
		for($i = $startIndex; $i < $endIndex; $i++)
		{
			$args [] = func_get_arg($i);
		}
		
		return call_user_func_array($f, $args);
	}

	protected $m_implementation;
}
