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

/**
 * Represents one of the Database
 * of a Database Datasource
 *
 * When the Database Datasource doesn't have the concept of separate databases,
 * it should implements ISQLTableRetriever to be able to provide Table access
 */
class Database extends SQLObject implements IExpression, ITableProvider
{

	public function __construct(Datasource $Datasource, $a_name, DatabaseStructure $structure = null)
	{
		parent::__construct($structure);
		$this->m_datasource = $Datasource;
		$this->m_strName = $a_name;
	}

	public function __get($member)
	{
		if ($member == 'datasource')
		{
			return $this->getDatasource();
		}
		elseif ($member == 'name')
		{
			return $this->m_strName;
		}
		
		return parent::__get($member);
	}
	
	// ns\IExpression implementations
	

	/**
	 * Format table name for query usage
	 *
	 * @param $a_options (ignored)        	
	 * @return string
	 */
	public function expressionString($a_options = null)
	{
		return $this->datasource->encloseElement($this->getName());
	}
	
	// IExpression implementation
	

	/**
	 *
	 * @return Datasource
	 */
	public final function getDatasource()
	{
		return $this->m_datasource;
	}
	
	// ITableProvider implementation
	

	/**
	 * Create a Table
	 *
	 * @param table $a_aParameters
	 *        	with key: name, alias (optional) & class (optional)
	 * @return Table
	 */
	public function getTable($a_name, $alias = null, $className = null, $useAliasAsName = false)
	{
		// ns\Reporter::debug($this, __METHOD__.'(): getTable ' . $a_name, __FILE__, __LINE__);
		$subStructure = null;
		if ($this->structure)
		{
			$subStructure = $this->structure->offsetGet($a_name);
		}
		
		$res = tableProviderGenericTableObjectMethod($this, $subStructure, $a_name, $alias, $className, $useAliasAsName);
		return $res;
	}

	public function tableIterator()
	{
		if ($this->structure)
		{
			return $this->structure;
		}
		
		return null;
	}

	/**
	 * Indicates if the table exists
	 *
	 * @see ITableProvider::tableExists()
	 */
	public function tableExists($a_name, $a_mode = kObjectQuerySchema)
	{
		$result = true;
		if ($a_mode & kObjectQuerySchema)
		{
			if ($this->structure)
			{
				$result = ($this->structure->offsetExists($a_name));
			}
			else
			{
				return false;
			}
		}
		
		if ($a_mode & kObjectQueryDatasource)
		{
			$a = $this->m_datasource->getTableSetStructure($this, false);
			return (($a instanceof DatabaseStructure) && $a->offsetExists($a_name) && ($a [$a_name] instanceof TableStructure));
		}
		
		return $result;
	}
	
	// ITableProvider implementation
	public final function getName()
	{
		return $this->m_strName;
	}
	
	// Members
	protected $m_datasource;

	protected $m_strName;
}

?>
