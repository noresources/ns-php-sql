<?php

/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 * 
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources as ns;

require_once (NS_PHP_PATH . '/core/arrays.php');

/**
 * Table representation class
 *
 * Provide data source independant methods
 * to manipulate data
 */
class Table extends SQLObject implements IExpression, IAliasedClone, ITableColumnProvider
{

	// construction / destruction
	public function __construct(ITableProvider $a_owner, $a_name, $a_aliasName = '', TableStructure $a_structure = null)
	{
		parent::__construct($a_structure, __NAMESPACE__ . '\\TableStructure');
		if (!(($a_owner instanceof Datasource) || ($a_owner instanceof TableSet)))
		{
			return ns\Reporter::fatalError($this, __METHOD__ . '(): Invalid owner class "' . get_class($a_owner) . '"');
		}
		$this->m_owner = $a_owner;
		$this->m_name = $a_name;
		$this->m_aliasName = $a_aliasName;
	}

	public function __destruct()
	{}

	public function __get($member)
	{
		if ($member == 'datasource')
		{
			return $this->getDatasource();
		}
		elseif ($member == 'owner')
		{
			return $this->m_owner;
		}
		elseif ($member == 'tableset')
		{
			return (($this->m_owner instanceof TableSet) ? $this->m_owner : null);
		}
		elseif ($member == 'rowCount')
		{
			return $this->getRowCount();
		}
		
		return parent::__get($member);
	}

	// ns\IExpression implementation
	
	/**
	 * Enter description here...
	 *
	 * @param int $a_options ELEMENT_*
	 * @return string
	 */
	public function expressionString($a_options = null)
	{
		$direct = ($this->m_owner instanceof Datasource) ? true : false;
		$Datasource = (($direct) ? $this->m_owner : $this->m_owner->getDatasource());
		$db = ($direct) ? '' : $this->m_owner->expressionString(kExpressionElementAlias) . '.';
		if (($a_options & kExpressionElementDeclaration) == kExpressionElementDeclaration)
		{
			return $db . $Datasource->encloseElement($this->m_name) . ($this->hasAlias() ? ' AS ' . $Datasource->encloseElement($this->alias()) : '');
			;
		}
		elseif ($a_options == kExpressionElementName)
		{
			return $db . $Datasource->encloseElement($this->m_name);
		}
		elseif ($this->hasAlias())
		{
			return $Datasource->encloseElement($this->alias());
		}
		
		return $db . ($Datasource->encloseElement($this->m_name));
	}

	// end of ns\IExpression implementation
	
	// IExpression implementation
	
	/**
	 *
	 * @return Datasource
	 */
	public function getDatasource()
	{
		if (($this->m_owner instanceof TableSet))
		{
			return $this->m_owner->getDatasource();
		}
		
		return $this->m_owner;
	}

	// IAliasedClone implementation
	/**
	 * {@inheritDoc}
	 * @see \NoreSources\SQL\IAliasedClone::cloneWithOtherAlias()
	 * @return \NoreSources\SQL\Table
	 */
	public function cloneWithOtherAlias($a_aliasName)
	{
		if ($a_aliasName == $this->alias())
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Alias is the same than the current object');
		}
		
		$cn = get_class($this);
		$result = new $cn($this->m_owner, $this->m_name, $a_aliasName);
		$result->m_structure = $this->structure;
		return $result;
	}

	// ITableColumnProvider implementation
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see sources/sql/ITableColumnProvider#defaultFieldClassName()
	 */
	public function defaultColumnClassName()
	{
		return __NAMESPACE__ . '\\TableColumn';
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see sources/sql/ITableColumnProvider#defaultStarFieldClassName()
	 */
	public function defaultStarColumnClassName()
	{
		return __NAMESPACE__ . '\\StarColumn';
	}

	/**
	 * Create a StarColumn
	 *
	 * @param $a_className optional
	 * @return StarColumn
	 */
	public function starColumnObject($a_className = null)
	{
		if (!class_exists($a_className))
		{
			$a_className = $this->defaultStarColumnClassName();
		}
		
		$res = new $a_className($this);
		return $res;
	}

	/**
	 *
	 * @see include/ns/php/lib/sources/sql/ITableColumnProvider#getColumn($a_name, $a_aliasName, $a_className)
	 * @return TableColumn
	 */
	public function getColumn($a_name, $a_aliasName = null, $a_className = null)
	{
		if ($a_name == '*')
		{
			$res = $this->starColumnObject(strlen($a_className) ? $a_className : $this->defaultStarColumnClassName());
			return $res;
		}
		
		$subStructure = null;
		if ($this->structure)
		{
			$subStructure = $this->structure->offsetGet($a_name);
			if (!$subStructure)
			{
				ns\Reporter::warning($this, __METHOD__ . '(): No structure for column ' . $a_name . ' of table ' . $this->m_name, __FILE__, __LINE__);
			}
		}
		else
		{
			ns\Reporter::warning($this, __METHOD__ . '(): No structure for table ' . $this->m_name, __FILE__, __LINE__);
		}
		
		$class = strlen($a_className) ? $a_className : $this->defaultColumnClassName();
		
		$obj = new $class($this, $a_name, $a_aliasName, $subStructure);
		
		return $obj;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see sources/sql/ITableColumnProvider#fieldIterator()
	 */
	public function columnIterator()
	{
		if ($this->m_structure)
		{
			return $this->m_structure;
		}
		
		return null;
	}

	/**
	 *
	 * @see sources/sql/ITableColumnProvider#columnExists($a_name)
	 */
	public function columnExists($a_name)
	{
		if ($a_name == '*')
		{
			return true;
		}
		if ($this->structure)
		{
			return $this->structure->offsetExists($a_name);
		}
		
		return true;
	}

	/**
	 * Indicates if table has an alias name
	 *
	 * @return bool
	 */
	public final function hasAlias()
	{
		return is_string($this->m_aliasName) && strlen($this->m_aliasName);
	}

	/**
	 * Number of row in the table
	 *
	 * @param $a_oWhereCondition ns\IExpression to filter results
	 * @param $a_oJoins SelectQueryJoin
	 * @return numeric
	 */
	public final function getRowCount(ns\IExpression $a_oWhereCondition = null, $a_oJoins = null)
	{
		$oQuery = new SelectQuery($this);
		
		$star = new FormattedData('*');
		$e = new SQLFunction('COUNT', $star);
		$oQuery->addColumn($e);
		
		if ($a_oWhereCondition)
		{
			$oQuery->where->expression($a_oWhereCondition);
		}
		
		if (($a_oJoins instanceof SelectQueryJoin))
		{
			$oQuery->addJoin($a_oJoins);
		}
		elseif (is_array($a_oJoins))
		{
			foreach ($a_oJoins as &$j)
			{
				if (($j instanceof SelectQueryJoin))
				{
					$oQuery->addJoin($j);
				}
			}
		}
		
		$res = false;
		if (!($res = $oQuery->execute()))
		{
			return false;
		}
		
		$r = $res->currentRow();
		
		return intval($r[0]);
	}

	/**
	 * Insert a new row
	 *
	 * @param $a_fieldsAndValues Associative array of field/value pair
	 * @return InsertQueryResult
	 */
	public function insert($a_fieldsAndValues)
	{
		if (!is_array($a_fieldsAndValues))
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Invalid parameter. Array expected', __FILE__, __LINE__);
		}
		
		$iq = new InsertQuery($this);
		$iq->addColumnValues($a_fieldsAndValues);
		
		return $iq->execute();
	}

	/**
	 *
	 * @param $a_fieldAndValues
	 * @param $a_conditions
	 * @return UpdateQueryResult
	 */
	public function update($a_fieldAndValues, ns\IExpression $a_conditions = null)
	{
		if (!is_array($a_fieldsAndValues))
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Invalid parameter. Array expected', __FILE__, __LINE__);
		}
		
		if (is_null($a_conditions))
		{
			ns\Reporter::notice($this, __METHOD__ . '(): Null condition will update all table rows', __FILE__, __LINE__);
		}
		
		$uq = new UpdateQuery($this);
		$uq->addColumnValues($a_fieldAndValues);
		$uq->where->addAndExpression($a_conditions);
		
		return $uq->execute();
	}

	/**
	 * Delete a set of rows, depending of conditions
	 *
	 * @param ns\IExpression $a_conditions
	 * @return DeleteQueryResult
	 */
	public function delete(ns\IExpression $a_conditions = null)
	{
		if (is_null($a_conditions))
		{
			ns\Reporter::notice($this, __METHOD__ . '(): Null condition will update all table rows', __FILE__, __LINE__);
		}
		
		$dq = new DeleteQuery($this);
		$dq->where->addAndExpression($a_conditions);
		
		return $dq->execute();
	}

	/**
	 * Table name
	 *
	 * @return string
	 */
	public final function getName()
	{
		return $this->m_name;
	}

	/**
	 * Table alias name
	 *
	 * @return string
	 */
	public final function alias()
	{
		return $this->m_aliasName;
	}

	/**
	 * Table owner.
	 * Datasource or TableSet, depending of the SQL Server system
	 *
	 * @return SQLObject
	 */
	public function owner()
	{
		return $this->m_owner;
	}

	/**
	 *
	 * @return TableSet
	 */
	public function tableSet()
	{
		$v = null;
		if (($this->m_owner instanceof TableSet))
		{
			$v = $this->m_owner;
		}
		
		return $v;
	}

	/** Legacy */
	public function database()
	{
		return $this->tableSet();
	}

	/**
	 * Table name
	 *
	 * @var string
	 */
	protected $m_name;

	/**
	 * Table alias
	 *
	 * @var string
	 */
	protected $m_aliasName;

	/**
	 * TableSet or Datasource reference
	 *
	 * @var SQLObject
	 */
	protected $m_owner;
}
