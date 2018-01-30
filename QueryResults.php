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
		$this->m_datasource = $datasource;
		$this->m_resultResource = $resultResource;
	}

	public function __destruct()
	{
		if ($this->m_datasource && $this->m_resultResource)
		{
			$this->datasource->freeResult($this);
		}
	}

	public function __get($member)
	{
		if ($member == 'datasource')
		{
			return $this->m_datasource;
		}
		elseif ($member == 'resultResource')
		{
			return $this->m_resultResource;
		}
		
		throw new \InvalidArgumentException(get_class($this) . '::' . $member);
	}

	/**
	 * Reference Query
	 *
	 * @var Datasource
	 */
	protected $m_datasource;

	/**
	 * A query result resource.
	 * Depends on the data source type
	 *
	 * @var unknown_type
	 */
	protected $m_resultResource;
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
		$this->m_iCurrentRowIndex = self::kIteratorIndexBefore;
		$this->m_aColumnNames = null;
		$this->m_aCurrentRow = null;
		$this->m_flags = self::kStateUnitialized;
		if ($fetchFlags == 0)
		{
			$fetchFlags = kRecordsetFetchBoth;
		}
		
		$this->m_flags |= ($fetchFlags & kRecordsetFetchBoth);
	}

	// Iterator implementation
	public function rewind()
	{
		$this->m_iCurrentRowIndex = self::kIteratorIndexBefore;
		$this->m_aCurrentRow = null;
		$this->m_flags |= self::kStateUnitialized;
		$this->m_flags &= ~self::kStateIteratorEnd;
		
		if ($this->datasource->resetResult($this))
		{
			$this->m_aCurrentRow = $this->datasource->fetchResult($this, ($this->m_flags & kRecordsetFetchBoth));
			if ($this->m_aCurrentRow)
			{
				$this->m_iCurrentRowIndex = 0;
				$this->m_flags &= ~self::kStateUnitialized;
			}
		}
		else
		{
			$this->m_flags |= self::kStateIteratorEnd;
		}
	}

	public function current()
	{
		if (($this->m_iCurrentRowIndex == self::kIteratorIndexBefore) && !($this->m_flags & self::kStateIteratorEnd))
		{
			$this->next();
		}
		
		return $this->m_aCurrentRow;
	}

	public function key()
	{
		return $this->m_iCurrentRowIndex;
	}

	public function next()
	{
		$this->m_aCurrentRow = $this->datasource->fetchResult($this, ($this->m_flags & kRecordsetFetchBoth));
		if ($this->m_aCurrentRow)
		{
			$this->m_iCurrentRowIndex++;
		}
		else
		{
			$this->m_iCurrentRowIndex = self::kIteratorIndexBefore;
			$this->m_flags |= self::kStateIteratorEnd;
		}
		
		return $this->m_aCurrentRow;
	}

	public function valid()
	{
		return !($this->m_flags & self::kStateIteratorEnd);
	}

	// End of Iterator implementation
	
	// Countable implementation
	
	/**
	 *
	 * @see sources/extensions/spl/NSCountable#count()
	 */
	public function count()
	{
		return $this->rowCount();
	}

	// End of Countable implementation
	
	/**
	 * Return the number of selected rows.
	 * @attention This feature is not available on some data sources like certain ODBC drivers.
	 *
	 * @return int or false if feature is not supported
	 */
	public function rowCount()
	{
		if ($this->m_flags & self::kRowCount)
		{
			return $this->m_rowCount;
		}
		
		$this->m_rowCount = $this->datasource->resultRowCount($this);
		if ($this->m_rowCount !== false)
		{
			$this->m_flags |= self::kRowCount;
		}
		
		return $this->m_rowCount;
	}

	/**
	 *
	 * @return unknown_type
	 */
	function currentRow()
	{
		return $this->current();
	}

	/**
	 *
	 * @return unknown_type
	 */
	function currentRowIndex()
	{
		if ($this->valid())
		{
			return $this->m_iCurrentRowIndex;
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
		if (!$this->m_aColumnNames && !($this->m_aColumnNames = $this->datasource->recordsetColumnArray($this)))
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Ubable to retrieve Column list');
		}
		
		return $this->m_aColumnNames;
	}

	/**
	 * Fetch all rows to an array
	 * @return array
	 */
	public function dataArray()
	{
		$data = array ();
		foreach ($this as $row)
		{
			$data[] = $row;
		}
		
		return $data;
	}

	/**
	 *
	 * @param string|integer $columnKey Column name or index to use as row key
	 * @return array
	 */
	public function keyedDataArray($columnKey)
	{
		$data = array ();
		foreach ($this as $row)
		{
			if (\array_key_exists($row[$columnKey], $data))
			{
				ns\Reporter::warning($this, __METHOD__ . '(): Duplicated key "' . $row[$columnKey] . '"');
				continue;
			}
			$data[$row[$columnKey]] = $row;
		}
		
		return $data;
	}

	protected $m_aCurrentRow;

	protected $m_iCurrentRowIndex;

	protected $m_aColumnNames;

	private $m_flags;

	private $m_rowCount;
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
					$this->lastInsertId = $table->getDatasource()->lastInsertId($this);
					break;
				}
			}
		}
		else
		{
			$this->lastInsertId = $table->getDatasource()->lastInsertId($this);
		}
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
		$this->m_affectedRowCount = null;
	}

	/**
	 *
	 * @return integer
	 */
	public function getAffectedRowCount()
	{
		if (\is_null($this->getAffectedRowCount))
		{
			$this->m_affectedRowCount = $this->datasource->getAffectedRowCount($this);
		}
		
		return $this->m_affectedRowCount;
	}

	private $m_affectedRowCount;
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
		$this->m_affectedRowCount = null;
	}

	/**
	 * @return integer
	 */
	public function getAffectedRowCount()
	{
		if (\is_null($this->m_affectedRowCount))
		{
			$this->m_affectedRowCount = $this->datasource->getAffectedRowCount($this);
		}
		
		return $this->m_affectedRowCount;
	}
	
	private $m_affectedRowCount;
}

?>
