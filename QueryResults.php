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

	static function queryResultClassName($a_strQuery)
	{
		$prefix = __NAMESPACE__ . '\\';
		if (preg_match('/^[ \t\n\r]*SELECT/i', $a_strQuery))
		{
			return $prefix . 'Recordset';
		}
		elseif (preg_match('/^[ \t\n\r]*INSERT[ \t\n\r]+INTO/i', $a_strQuery))
		{
			return $prefix . 'InsertQueryResult';
		}
		elseif (preg_match('/^[ \t\n\r]*UPDATE/i', $a_strQuery))
		{
			return $prefix . 'UpdateQueryResult';
		}
		elseif (preg_match('/^[ \t\n\r]*DELETE[ \t\n\r]+FROM/i', $a_strQuery))
		{
			return $prefix . 'DeleteQueryResult';
		}
		
		return $prefix . 'Recordset';
	}

	public function __construct(Datasource $a_datasource, $a_resultResource)
	{
		$this->m_datasource = $a_datasource;
		$this->m_resultResource = $a_resultResource;
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
	 * Depend of used database
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
	const ITERATOR_BEFORE_RECORD = -1;
	const FLAG_ROWCOUNT = 1;
	const FLAG_END = 2;
	const FLAG_UNINITIALIZED = 4;

	public function __construct(Datasource $a_datasource, $a_resultResource)
	{
		parent::__construct($a_datasource, $a_resultResource);
		$this->m_iCurrentRowIndex = self::ITERATOR_BEFORE_RECORD;
		$this->m_aColumnNames = null;
		$this->m_aCurrentRow = null;
		$this->m_flags = self::FLAG_UNINITIALIZED;
	}
	
	// Iterator implementation
	public function rewind()
	{
		// ns\Reporter::debug($this, 'rewind '.$this->m_iCurrentRowIndex);
		$this->m_iCurrentRowIndex = self::ITERATOR_BEFORE_RECORD;
		$this->m_aCurrentRow = null;
		$this->m_flags |= self::FLAG_UNINITIALIZED;
		$this->m_flags &= ~self::FLAG_END;
		
		if ($this->datasource->resetResult($this))
		{
			$this->m_aCurrentRow = $this->datasource->fetchResult($this);
			if ($this->m_aCurrentRow)
			{
				$this->m_iCurrentRowIndex = 0;
				$this->m_flags &= ~self::FLAG_UNINITIALIZED;
			}
		}
		else
		{
			$this->m_flags |= self::FLAG_END;
		}
	}

	public function current()
	{
		// ns\Reporter::debug($this, 'current '.$this->m_iCurrentRowIndex);
		if (($this->m_iCurrentRowIndex == self::ITERATOR_BEFORE_RECORD) && !($this->m_flags & self::FLAG_END))
		{
			$this->next();
		}
		
		return $this->m_aCurrentRow;
	}

	public function key()
	{
		//ns\Reporter::debug($this, 'next '. $this->m_iCurrentRowIndex);
		return $this->m_iCurrentRowIndex;
	}

	public function next()
	{
		//ns\Reporter::debug($this, 'next '.$this->m_iCurrentRowIndex);
		$this->m_aCurrentRow = $this->datasource->fetchResult($this);
		if ($this->m_aCurrentRow)
		{
			$this->m_iCurrentRowIndex++;
		}
		else
		{
			$this->m_iCurrentRowIndex = self::ITERATOR_BEFORE_RECORD;
			$this->m_flags |= self::FLAG_END;
		}
		
		return $this->m_aCurrentRow;
	}

	public function valid()
	{
		return !($this->m_flags & self::FLAG_END);
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
		if ($this->m_flags & self::FLAG_ROWCOUNT)
		{
			//ns\Reporter::debug($this, 'Use buffered row count '. $this->m_rowCount);
			return $this->m_rowCount;
		}
		
		$this->m_rowCount = $this->datasource->resultRowCount($this);
		if ($this->m_rowCount !== false)
		{
			$this->m_flags |= self::FLAG_ROWCOUNT;
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
	function columnNames()
	{
		if (!$this->m_aColumnNames && !($this->m_aColumnNames = $this->datasource->recordsetColumnArray($this)))
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Ubable to retrieve Column list');
		}
		
		return $this->m_aColumnNames;
	}

	public function dataArray()
	{
		$data = array ();
		foreach ($this as $row)
		{
			$data [] = $row;
		}
		
		return $data;
	}

	public function keyedDataArray($a_columnKey)
	{
		$data = array ();
		foreach ($this as $row)
		{
			if (\array_key_exists($row [$a_columnKey], $data))
			{
				ns\Reporter::warning($this, __METHOD__ . '(): Duplicated key "' . $row [$a_columnKey] . '"');
				continue;
			}
			$data [$row [$a_columnKey]] = $row;
		}
		
		return $data;
	}

	/**
	 * Create a XHTML <select> tag
	 * 
	 * @param $a_valueField Field
	 *        	used as value attribute
	 * @param $a_textField Field
	 *        	used as text node
	 * @param $a_selectedValue Selected
	 *        	value
	 * @param $a_selectAttributes Attributes
	 *        	of the select tag
	 * @param $a_optionsAttribute Attributes
	 *        	of the option tags
	 * @param $a_dom DOMDocument        	
	 * @return DOMElement
	 */
	public function xhtmlSelectTag($a_valueField, $a_textField, $a_selectedValue = null, $a_selectAttributes = null, $a_optionsAttribute = null, DOMDocument $a_dom = null)
	{
		// xhtml is an optional package
		if (!method_exists('xhtml', 'array_to_select_tag'))
		{
			$file = ns_find_lib_source('xhtml/xhtml');
			if (!$file)
			{
				return ns\Reporter::error($this, __METHOD__ . '(): xhtml module could not be found', __FILE__, __LINE__);
			}
			
			require_once ($file);
		}
		
		return xhtml::array_to_select_tag($this, $a_valueField, $a_textField, $a_selectedValue, $a_selectAttributes, $a_optionsAttribute, $a_dom);
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

	public function __construct(Datasource $a_datasource, $a_resultResource)
	{
		parent::__construct($a_datasource, $a_resultResource);
		$this->m_iLastInsertId = $this->datasource->lastInsertId($this->resultResource);
	}

	public function lastInsertId()
	{
		return $this->m_iLastInsertId;
	}

	protected $m_iLastInsertId;
}

class UpdateQueryResult extends QueryResult
{

	public function __construct(Datasource $a_datasource, $a_resultResource)
	{
		parent::__construct($a_datasource, $a_resultResource);
	}

	public function affectedRowCount()
	{
		return $this->datasource->affectedRowCount($this);
	}
}

class DeleteQueryResult extends QueryResult
{

	public function __construct(Datasource $a_datasource, $a_resultResource)
	{
		parent::__construct($a_datasource, $a_resultResource);
		$this->m_iAffectedRows = $this->datasource->affectedRowCount($this);
	}

	public function affectedRowCount()
	{
		return $this->m_iAffectedRows;
	}

	protected $m_iAffectedRows;
}

?>