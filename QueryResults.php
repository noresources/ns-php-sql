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
use \Iterator;
use \Countable;

require_once ('base.php');

/**
 * Base class for all query results
 *
 * @author renaud
 */
class QueryResult
{

	/**
	 * Guess QueryResult class from the SQL command string
	 *
	 * @param string $queryString
	 * @return string QueryResult
	 */
	static function queryResultClassName($queryString)
	{
		$prefix = __NAMESPACE__ . '\\';
		if (preg_match('/^[ \t\n\r]*SELECT/i', $queryString))
		{
			return $prefix . 'Recordset';
		}
		elseif (preg_match('/^[ \t\n\r]*INSERT[ \t\n\r]+INTO/i', $queryString))
		{
			return $prefix . 'InsertQueryResult';
		}
		elseif (preg_match('/^[ \t\n\r]*UPDATE/i', $queryString))
		{
			return $prefix . 'UpdateQueryResult';
		}
		elseif (preg_match('/^[ \t\n\r]*DELETE[ \t\n\r]+FROM/i', $queryString))
		{
			return $prefix . 'DeleteQueryResult';
		}
		
		return $prefix . 'Recordset';
	}

	/**
	 *
	 * @param Datasource $datasource
	 * @param resource $resultResource DBMS dependant result resource
	 */
	public function __construct(Datasource $datasource, $resultResource)
	{
		$this->datasource = $datasource;
		$this->resultResource = $resultResource;
	}

	/**
	 * Release the inner result resource
	 */
	public function __destruct()
	{
		if ($this->datasource && $this->resultResource)
		{
			$this->datasource->freeResult($this);
		}
	}

	/**
	 *
	 * @param string $member
	 * @throws \InvalidArgumentException
	 * @return \NoreSources\SQL\Datasource|\NoreSources\SQL\unknown_type
	 */
	public function __get($member)
	{
		if ($member == 'datasource')
			return $this->datasource;
		elseif ($member == 'resultResource')
			return $this->resultResource;
		
		throw new \InvalidArgumentException(get_class($this) . '::' . $member);
	}

	public function __call($member, $args)
	{
		if (count($args) == 0)
		{
			$functionName = 'get' . strtoupper(substr($member, 0, 1)) . substr($member, 1);
			
			if (method_exists($this, $functionName))
			{
				ns\Reporter::notice($this, 'Attempting to call ' . $member . '(). Did you mean ' . $functionName . '() ?');
			}
			
			return $this->__get($member);
		}
	}

	/**
	 * Reference Query
	 *
	 * @var Datasource
	 */
	protected $datasource;

	/**
	 * A query result resource.
	 * Depends on the data source type
	 *
	 * @var unknown_type
	 */
	protected $resultResource;
}

/**
 * Result of a SELECT query
 */
class Recordset extends QueryResult implements Iterator, Countable
{
	const kIteratorIndexBefore = -1;
	const kStateUnitialized = 0x10000000;
	const kStateIteratorEnd = 0x20000000;
	const kRowCount = 0x40000000;

	/**
	 * Recordset result
	 * @param Datasource $datasource
	 * @param resource $resultResource
	 * @param integer $fetchFlags
	 */
	public function __construct(Datasource $datasource, $resultResource, $fetchFlags = kRecordsetFetchBoth)
	{
		parent::__construct($datasource, $resultResource);
		$this->currentRowIndex = self::kIteratorIndexBefore;
		$this->columnNames = null;
		$this->currentRow = null;
		$this->flags = self::kStateUnitialized;
		if ($fetchFlags == 0)
		{
			$fetchFlags = kRecordsetFetchBoth;
		}
		
		$this->flags |= ($fetchFlags & kRecordsetFetchBoth);
	}

	public function rewind()
	{
		$this->currentRowIndex = self::kIteratorIndexBefore;
		$this->currentRow = null;
		$this->flags |= self::kStateUnitialized;
		$this->flags &= ~self::kStateIteratorEnd;
		
		if ($this->datasource->resetResult($this))
		{
			$this->currentRow = $this->datasource->fetchResult($this, ($this->flags & kRecordsetFetchBoth));
			if ($this->currentRow)
			{
				$this->currentRowIndex = 0;
				$this->flags &= ~self::kStateUnitialized;
			}
		}
		else
		{
			$this->flags |= self::kStateIteratorEnd;
		}
	}

	public function current()
	{
		if (($this->currentRowIndex == self::kIteratorIndexBefore) && !($this->flags & self::kStateIteratorEnd))
		{
			$this->next();
		}
		
		return $this->currentRow;
	}

	public function key()
	{
		return $this->currentRowIndex;
	}

	public function next()
	{
		$this->currentRow = $this->datasource->fetchResult($this, ($this->flags & kRecordsetFetchBoth));
		if ($this->currentRow)
		{
			$this->currentRowIndex++;
		}
		else
		{
			$this->currentRowIndex = self::kIteratorIndexBefore;
			$this->flags |= self::kStateIteratorEnd;
		}
		
		return $this->currentRow;
	}

	public function valid()
	{
		return !($this->flags & self::kStateIteratorEnd);
	}

	public function count()
	{
		return $this->rowCount;
	}

	/**
	 *
	 * @return mixed
	 */
	public function __get($member)
	{
		if ($member == 'rowCount')
			return $this->getRowCount();
		elseif ($member == 'currentRow')
			return $this->current();
		elseif ($member == 'currentRowIndex')
			return $this->getCurrentRowIndex();
		elseif ($member == 'columnNames')
			return $this->getColumnNames();
		
		return parent::__get($member);
	}

	/**
	 * Return the number of selected rows.
	 * @attention This feature is not available on some data sources like certain ODBC drivers.
	 *
	 * @return int or false if feature is not supported
	 */
	public function getRowCount()
	{
		if ($this->flags & self::kRowCount)
		{
			return $this->rowCount;
		}
		
		$this->rowCount = $this->datasource->resultRowCount($this);
		if ($this->rowCount !== false)
		{
			$this->flags |= self::kRowCount;
		}
		
		return $this->rowCount;
	}

	/**
	 *
	 * @return unknown_type
	 */
	function getCurrentRowIndex()
	{
		if ($this->valid())
		{
			return $this->currentRowIndex;
		}
		return -1;
	}

	/**
	 * List of query columns name
	 *
	 * @return array
	 */
	function getColumnNames()
	{
		if (!$this->columnNames && !($this->columnNames = $this->datasource->recordsetColumnArray($this)))
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Ubable to retrieve Column list');
		}
		
		return $this->columnNames;
	}

	protected $currentRow;

	protected $currentRowIndex;

	protected $columnNames;

	private $flags;

	private $rowCount;
}

/**
 * Result of a INSERT query
 *
 * Can return the last inserted id
 */
class InsertQueryResult extends QueryResult
{

	/**
	 *
	 * @param Table $table
	 * @param unknown $resultResource
	 */
	public function __construct(Table $table, $resultResource)
	{
		parent::__construct($table->getDatasource(), $resultResource);
		$structure = $table->getStructure();
		if ($structure)
		{
			foreach ($structure as $columnName => $column)
			{
				if ($column->getProperty(kStructureAutoincrement))
				{
					$this->lastInsertId = $table->getDatasource()->getLastInsertId($this);
					break;
				}
			}
		}
		else
		{
			$this->lastInsertId = $table->getDatasource()->getLastInsertId($this);
		}
	}

	public function __get($member)
	{
		if ($member == 'lastInsertId')
			return $this->getLastInsertId();
		return parent::__get($member);
	}

	/**
	 *
	 * @return integer|null Value of autoincremented column if available. Otherwise @c null
	 */
	public function getLastInsertId()
	{
		return $this->lastInsertId;
	}

	/**
	 *
	 * @var integer
	 */
	protected $lastInsertId;
}

class UpdateQueryResult extends QueryResult
{

	/**
	 *
	 * @param Datasource $datasource
	 * @param resource $resultResource
	 */
	public function __construct(Datasource $datasource, $resultResource)
	{
		parent::__construct($datasource, $resultResource);
		$this->affectedRowCount = null;
	}

	public function __get($member)
	{
		if ($member == 'affectedRowCount')
			return $this->getAffectedRowCount();
		return parent::__get($member);
	}

	/**
	 *
	 * @return integer
	 */
	public function getAffectedRowCount()
	{
		if (\is_null($this->affectedRowCount))
		{
			$this->affectedRowCount = $this->datasource->getAffectedRowCount($this);
		}
		
		return $this->affectedRowCount;
	}

	private $affectedRowCount;
}

class DeleteQueryResult extends QueryResult
{

	/**
	 *
	 * @param Datasource $datasource
	 * @param resource $resultResource
	 */
	public function __construct(Datasource $datasource, $resultResource)
	{
		parent::__construct($datasource, $resultResource);
		$this->affectedRowCount = null;
	}
	
	public function __get ($member)
	{
		if ($member == 'affectedRowCount') 
			return $this->getAffectedRowCount();
		return parent::__get($member);
	}

	/**
	 * @return integer
	 */
	public function getAffectedRowCount()
	{
		if (\is_null($this->affectedRowCount))
		{
			$this->affectedRowCount = $this->datasource->getAffectedRowCount($this);
		}
		
		return $this->affectedRowCount;
	}
	
	private $affectedRowCount;
}
