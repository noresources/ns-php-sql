<?php

/**
 * Copyright © 2012-2015 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 * 
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources as ns;

/**
 * Insert query builder
 * A InsertQuery works only on one set of values
 */
class InsertQuery extends TableQuery implements ns\IExpression
{

	public function __construct(Table $a_table)
	{
		parent::__construct($a_table);
		$this->m_fieldValues = array ();
	}

	public function execute()
	{
		$qs = $this->expressionString();
		if (!$qs)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Invalid query string', __FILE__, __LINE__);
		}
		
		$result = $this->m_datasource->executeQuery($qs);
		if ($result)
		{
			return new InsertQueryResult($this->datasource, $result);
		}
		return false;
	}

	public function expressionString($a_options = null)
	{
		if (count($this->m_fieldValues) == 0)
		{
			return false;
		}
		
		$qs = 'INSERT INTO ' . $this->table->expressionString(kExpressionElementName);
		
		$qs .= ' (' . ns\array_implode_keys(', ', $this->m_fieldValues);
		$qs .= ') VALUES (' . ns\array_implode_values(', ', $this->m_fieldValues) . ')';
		
		return $qs;
	}

	/**
	 * Add a field value
	 *
	 * @param mixed $a_field
	 *        	string (name) or TableField
	 * @param ns\IExpression $a_value        	
	 */
	public function addFieldValue($a_field, ns\IExpression $a_value)
	{
		$a_field = mixedToTableField($a_field, $this->table);
		if (!$a_field)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Unable to get TableField object', __FILE__, __LINE__);
		}
		
		$f = $this->datasource->encloseElement($a_field->name);
		
		$this->m_fieldValues [$f] = $a_value->expressionString();
	}

	/**
	 * Add multiple field values
	 *
	 * @param mixed $a_fieldAndValues
	 *        	associative array [field name => value]
	 *        	
	 *        	Values are formatted using TableField::importData()
	 */
	public function addFieldValues($a_fieldAndValues)
	{
		foreach ($a_fieldAndValues as $k => $v)
		{
			$k = mixedToTableField($k, $this->table);
			if (!$k)
			{
				ns\Reporter::error($this, __METHOD__ . '(): Invalid field ', __FILE__, __LINE__);
				continue;
			}
			
			$this->addFieldValue($k, $k->importData($v));
		}
	}

	public function clear()
	{
		$this->m_fieldValues = array ();
	}

	/**
	 * Field values
	 *
	 * @var associative array (formatted field name => formatted value)
	 */
	protected $m_fieldValues;
}

/**
 * Update query builder
 */
class UpdateQuery extends TableQuery implements ns\IExpression
{

	public function __construct(Table $a_table)
	{
		parent::__construct($a_table);
		$this->m_fieldValues = array ();
	}

	public function execute()
	{
		$qs = $this->expressionString();
		if (!$qs)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Invalid query string', __FILE__, __LINE__);
		}
		
		$result = $this->m_datasource->executeQuery($qs);
		if ($result)
		{
			return new UpdateQueryResult($this->datasource, $result);
		}
		return false;
	}

	public static function glueSetStatements($k, $v)
	{
		return ' ' . $k . '=' . $v;
	}

	public function expressionString($a_options = null)
	{
		/*
		 * if (count($this->m_fieldValues) == 0) { return ns\Reporter::error($this, __METHOD__.': No field set.'); }
		 */
		$qs = 'UPDATE ' . $this->table->expressionString(kExpressionElementName) . ' SET ';
		$qs .= ' ' . ns\array_implode_cb(', ', $this->m_fieldValues, array (
				get_class($this),
				'glueSetStatements' 
		));
		
		if ($this->m_condition)
		{
			$qs .= ' ' . $this->m_condition->expressionString();
		}
		
		return $qs;
	}

	/**
	 * Add a field value
	 *
	 * @param mixed $a_field
	 *        	string (name) or TableField
	 * @param ns\IExpression $a_value        	
	 */
	public function addFieldValue($a_field, ns\IExpression $a_value)
	{
		$a_field = mixedToTableField($a_field, $this->table);
		if (!$a_field)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Unable to get TableField object', __FILE__, __LINE__);
		}
		
		$f = $this->datasource->encloseElement($a_field->name);
		
		$this->m_fieldValues [$f] = $a_value->expressionString();
	}

	/**
	 * Add multiple field values
	 *
	 * @param mixed $a_fieldAndValues
	 *        	associative array [field name => value]
	 *        	
	 *        	Values are formatted using TableField::importData()
	 */
	public function addFieldValues($a_fieldAndValues)
	{
		foreach ($a_fieldAndValues as $k => $v)
		{
			$k = mixedToTableField($k, $this->table);
			if (!$k)
			{
				ns\Reporter::error($this, __METHOD__ . '(): Invalid field ', __FILE__, __LINE__);
				continue;
			}
			$this->addFieldValue($k, $k->importData($v));
		}
	}

	/**
	 *
	 * @param $a_cond ns\IExpression        	
	 * @return WhereQueryConditionStatement
	 */
	public function where(ns\IExpression $a_cond = null)
	{
		if ($a_cond)
		{
			$this->m_condition = $a_cond;
		}
		
		if (!$this->m_condition)
		{
			$this->m_condition = new WhereQueryConditionStatement();
		}
		
		return $this->m_condition;
	}

	protected $m_condition;

	/**
	 * Field values
	 *
	 * @var associative array (formatted field name => formatted value)
	 */
	protected $m_fieldValues;
}

class SelectQueryStaticValueColumn implements ns\IExpression
{

	public function __construct(SelectQuery $a_query, $a_value, $a_strAlias)
	{
		$this->m_oQuery = $a_query;
		$this->m_oValue = (($a_value instanceof ns\IExpression) ? $a_value : new FormattedData($a_value));
		$this->m_alias = (is_string($a_strAlias) && strlen($a_strAlias)) ? new SQLAlias($a_query->datasource, $a_strAlias) : null;
	}

	public function expressionString($a_options = null)
	{
		if ($this->m_alias)
		{
			if (($a_options & kExpressionElementDeclaration) == kExpressionElementDeclaration)
			{
				return $this->m_oValue->expressionString($a_options) . ' ' . $this->m_alias->expressionString($a_options);
			}
			elseif (($a_options & kExpressionElementAlias) == kExpressionElementAlias)
			{
				return $this->m_alias->expressionString($a_options);
			}
		}
		
		return $this->m_oValue->expressionString($a_options);
	}

	protected $m_oQuery;

	protected $m_oValue;

	protected $m_alias;
}

/**
 * A class to write a condition statement for a SQL query
 */
abstract class QueryConditionStatement extends ns\UnaryOperatorExpression
{

	/**
	 * Constructor
	 *
	 * @param string $a_strOperator
	 *        	Operator
	 * @param ns\IExpression $a_oExpression
	 *        	Expression representing the condition(s)
	 */
	public function __construct($a_strOperator, ns\IExpression $a_oExpression = null)
	{
		parent::__construct($a_strOperator, $a_oExpression, false);
	}

	/**
	 * Add a condition with a AND operator if an expression is already set.
	 *
	 * @param ns\IExpression $a_oExpression        	
	 * @return ns\IExpression condition(s)
	 */
	public function addAndExpression(ns\IExpression $a_oExpression)
	{
		if (!($this->m_expression instanceof ns\IExpression))
		{
			$this->m_expression = $a_oExpression;
		}
		else
		{
			$this->m_expression = new SQLAnd($this->m_expression, $a_oExpression);
		}
		
		$this->m_expression->protect(false);
		return $this->m_expression;
	}

	/**
	 * Add a condition with a OR operator if an expression is already set.
	 *
	 * @param ns\IExpression $a_oExpression        	
	 * @return ns\IExpression condition(s)
	 */
	public function addOrExpression(ns\IExpression $a_oExpression)
	{
		if (($this->m_expression instanceof ns\IExpression))
		{
			$this->m_expression = $a_oExpression;
		}
		else
		{
			$this->m_expression = new SQLOr($this->m_expression, $a_oExpression);
		}
		
		return $this->m_expression;
	}
}

/**
 * A WHERE statement generator
 */
class WhereQueryConditionStatement extends QueryConditionStatement
{

	public function __construct()
	{
		parent::__construct('WHERE');
		$this->protect(false);
	}
}

/**
 * A HAVING statement generator
 */
class HavingQueryConditionStatement extends QueryConditionStatement
{

	public function __construct()
	{
		parent::__construct('HAVING');
		$this->protect(false);
	}
}

class DeleteQuery extends TableQuery
{

	public function __construct(Table $a_table)
	{
		parent::__construct($a_table);
		$this->m_condition = new WhereQueryConditionStatement();
	}

	public function execute()
	{
		$qs = $this->expressionString();
		if (!$qs)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Invalid query string', __FILE__, __LINE__);
		}
		
		$result = $this->m_datasource->executeQuery($qs);
		if ($result !== false)
		{
			return new DeleteQueryResult($this->datasource, $result);
		}
		return false;
	}

	public function __get($key)
	{
		if ($key == 'where')
		{
			if (!$this->m_condition)
			{
				$this->m_condition = new WhereQueryConditionStatement();
			}
			
			return $this->m_condition;
		}
		
		return parent::__get($key);
	}
	
	public function expressionString($a_options = null)
	{
		$qs = 'DELETE FROM ' . $this->table->expressionString(kExpressionElementDeclaration);
		
		if ($this->where->expression() !== null)
		{
			$qs .= ' ' . $this->m_condition->expressionString(kExpressionElementName);
		}
		
		return $qs;
	}

	protected $m_condition;
}

/**
 * A LIMIT statement generator
 */
class SelectQueryLimitStatement implements ns\IExpression
{

	public function __construct($offset, $limit)
	{
		$this->m_iOffset = round($offset);
		$this->m_iLimit = round($limit);
	}

	/**
	 * Implementation of ns\IExpression method
	 */
	public function expressionString($a_options = null)
	{
		if ($this->m_iOffset == 0)
		{
			return 'LIMIT ' . $this->m_iLimit;
		}
		
		return 'LIMIT ' . $this->m_iLimit . ' OFFSET ' . $this->m_iOffset;
	}
	
	/*
	 * Call self::expressionString() method with default parameter @return string
	 */
	public function __tostring()
	{
		return strval($this->expressionString());
	}

	public function add($value)
	{
		$value = round($value);
		$this->m_iOffset += $value;
		$this->m_iLimit += $value;
	}

	public function next()
	{
		$value = ($this->m_iLimit - $this->m_iOffset) + 1;
		$this->m_iOffset += $value;
		$this->m_iLimit += $value;
	}

	protected $m_iOffset;

	protected $m_iLimit;
}

/**
 * A GROUP BY statement generator
 */
class SelectQueryGroupByStatement implements ns\IExpression
{

	public function __construct(Datasource $a_datasource)
	{
		$this->m_columns = array ();
		$this->m_datasource = $a_datasource;
	}

	public static function glueGroupByStatement($k, $v, $selectColumns = null)
	{
		if (is_array($selectColumns))
		{
			$alias = $v->expressionString(kExpressionElementAlias);
			if (in_array($alias, $selectColumns))
			{
				return $alias;
			}
		}
	
		return $v->expressionString(kExpressionElementName);
	}
	
	/**
	 * Implementation of ns\IExpression method
	 * @param mixed $a_options Array of preformatted SELECT column names (or alias if any) 
	 * @return string
	 */
	public function expressionString($a_options = null)
	{
		if (count($this->m_columns))
		{
			return 'GROUP BY ' . ns\array_implode_cb(', ', $this->m_columns, array (get_class($this),'glueGroupByStatement'), $a_options);
		}
	
		return '';
	}

	/**
	 * Add a column in the field list
	 */
	public function addColumn(/* ... */)
	{
		$n = func_num_args();
		for($i = 0; $i < $n; $i++)
		{
			$c = func_get_arg($i);
			if (($c instanceof SQLAlias) || ($c instanceof ITableField))
			{
				$this->m_columns [] = $c;
			}
			elseif (($c instanceof IAliasable) && $c->hasAlias())
			{
				$this->m_columns [] = $c->alias();
			}
			elseif (is_string($c))
			{
				$this->m_columns [] = new SQLAlias($this->m_datasource, $c);
			}
		}
		return $n;
	}
	
	public function clear()
	{
		$this->m_columns = array();
	}

	protected $m_columns;

	protected $m_datasource;
}

interface ISelectQueryOrderByStatement extends ns\IExpression
{
}

/**
 * Random order
 *
 * @todo this feature may not exists in all DB systems
 */
class SelectQueryRandomOrderByStatement implements ISelectQueryOrderByStatement
{

	public function __construct()
	{
	}

	public function expressionString($a_options = null)
	{
		return 'ORDER BY RAND()';
	}
}

class SelectQueryOrderByStatement implements ISelectQueryOrderByStatement
{

	public function __construct()
	{
		$this->m_columns = array ();
	}

	public static function glueOrderByStatement($k, $v, $selectColumns = null)
	{
		if (is_array($selectColumns))
		{
			$alias = $v [0]->expressionString(kExpressionElementAlias);
			if (in_array($alias, $selectColumns))
			{
				return $alias . ' ' . (($v [1]) ? 'ASC' : 'DESC');
			}
		}
		
		return $v [0]->expressionString(kExpressionElementName) . ' ' . (($v [1]) ? 'ASC' : 'DESC');
	}

	/**
	 * Implementation of ns\IExpression method
	 */
	public function expressionString($a_options = null)
	{
		if (count($this->m_columns))
		{
			return 'ORDER BY ' . ns\array_implode_cb(', ', $this->m_columns, array (get_class($this),'glueOrderByStatement'), $a_options);
		}
		
		return ''; 
	}

	public function clear()
	{
		$this->m_columns = array();
	}
	
	public function addColumn(ns\IExpression $a_column, $a_bAsc = true)
	{
		$this->addOrder($a_column, $a_bAsc);
	}
	
	/**
	 * Add a column in the field list
	 */
	public function addOrder(ns\IExpression $a_column, $a_bAsc = true)
	{
		$this->m_columns [] = array (
				$a_column,
				$a_bAsc 
		);
	}

	protected $m_columns;
}

abstract class ISelectQueryJoin extends ns\UnaryOperatorExpression
{
	public function __construct($a_joinType, Table $a_leftTable, Table $a_rightTable)
	{
		parent::__construct($a_leftTable->datasource->getDatasourceString($a_joinType), $a_rightTable);
		$this->m_leftTable = $a_leftTable;
		$this->m_rightTable = $a_rightTable;
		$this->m_joinType = $a_joinType;
		$this->protect(false);
	}
	
	public function __get($key)
	{
		if ($key == 'datasource')
		{
			$this->m_leftTable->datasource;
		}
		if ($key == 'joinType') 
		{
			return $this->m_joinType;
		} 
		elseif ($key == "leftTable")
		{
			return $this->m_leftTable;
		}
		elseif ($key == "rightTable")
		{
			return $this->m_rightTable;
		}
		
		throw new \InvalidArgumentException(get_class($this) . '::' . $member);
	}
	
	public function expressionString($a_options = null)
	{
		return parent::expressionString(kExpressionElementDeclaration);
	}

	protected $m_joinType;

	protected $m_leftTable;

	protected $m_rightTable;
}

class SelectQueryNaturalJoin extends ISelectQueryJoin
{

	public function __construct(Table $a_leftTable, Table $a_rightTable)
	{
		parent::__construct(kJoinNatural, $a_leftTable, $a_rightTable);
	}
}

class SelectQueryJoin extends ISelectQueryJoin
{

	public function __construct($a_joinType, Table $a_leftTable, Table $a_rightTable)
	{
		parent::__construct($a_joinType, $a_leftTable, $a_rightTable);
		$this->m_joinLink = null;
	}

	public function expressionString($a_options = null)
	{
		return parent::expressionString() . ($this->m_joinLink ? ' ' . $this->m_joinLink->expressionString(kExpressionElementName) : '');
	}

	public function addLink(TableField $a_leftField, TableField $a_rightField)
	{
		if ($a_leftField->table != $this->leftTable || $a_rightField->table != $this->rightTable)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Field(s) (is/are) not from the left/right table', __FILE__, __LINE__);
		}
		
		$e = new ns\BinaryOperatorExpression('=', $a_leftField, $a_rightField);
		$e->protect(false);
		
		if ($this->m_joinLink === null)
		{
			$this->m_joinLink = new ns\UnaryOperatorExpression('ON', $e);
		}
		else
		{
			$and = new SQLAnd($this->m_joinLink->expression(), $e);
			$and->protect(false);
			$this->m_joinLink->expression($and);
		}
		
		$this->m_joinLink->protect(true);
	}

	protected $m_joinLink;
}

/**
 * SELECT query generator
 */
class SelectQuery extends TableQuery implements ns\IExpression
{

	/**
	 * Constructor
	 *
	 * @param Datasource $a_oDatasource
	 *        	Connection to a database
	 * @param Table $a_table
	 *        	Main table of the SELECT query
	 */
	public function __construct(Table $a_table)
	{
		parent::__construct($a_table);
		$this->m_distinct = false;
		$this->m_having = new HavingQueryConditionStatement();
		$this->m_where = new WhereQueryConditionStatement();
		$this->m_unionQueries = array ();
		$this->m_joins = array ();
		$this->m_columns = array ();
		$this->m_randomOrder = false;
	}

	/**
	 * @return SelectQuery
	 */
	public function __clone()
	{
		// $this->m_columns = clone $this->m_columns;
		// $this->m_unionQueries = clone $this->m_unionQueries;
		
		// do not clone Datasource
		// $this->m_datasource = $this->m_datasource;
		if (is_object($this->m_group))
		{
			$this->m_group = clone $this->m_group;
		}
		
		if (is_object($this->m_where))
		{
			$this->m_where = clone $this->m_where;
		}
		
		if (is_object($this->m_having))
		{
			$this->m_having = clone $this->m_having;
		}
		
		if (is_object($this->m_limit))
		{
			$this->m_limit = clone $this->m_limit;
		}
		
		if (is_object($this->m_order))
		{
			$this->m_order = clone $this->m_order;
		}
		
		$this->m_table = clone $this->m_table;
	}
	
	/**
	 * @return mixed
	 */
	public function __get($key)
	{
		if ($key == 'columns')
		{
			return $this->m_columns;
		}
		else if ($key == 'columnCount')
		{
			return count($this->m_columns);
		}
		elseif ($key == 'groupBy')
		{
			if (!$this->m_group)
			{
				$this->m_group = new SelectQueryGroupByStatement($this->datasource);
			}
			
			return $this->m_group;
		}
		elseif ($key == 'orderBy')
		{
			if (!$this->m_order)
			{
				$this->m_order = new SelectQueryOrderByStatement();
			}
			
			return $this->m_order;
		}
		elseif ($key == 'where')
		{
			if (!$this->m_where)
			{
				$this->m_where = new WhereQueryConditionStatement();
			}
			
			return $this->m_where;
		}
		elseif ($key == 'having')
		{
			if (!$this->m_having)
			{
				$this->m_having = new HavingQueryConditionStatement();
			}
			
			return $this->m_having;
		}
				
		return parent::__get($key);
	}
	
	// Inherited methods
	public function expressionString($a_options = null)
	{
		$qs = 'SELECT ' . ($this->m_distinct ? 'DISTINCT ' : '');
		
		// columns
		$selectColumnAliases = array();
		if (count($this->m_columns))
		{
			foreach ($this->m_columns as $c)
			{
				$selectColumnAliases[] = $c->expressionString(kExpressionElementAlias);
			}
			
			$qs .= ns\array_implode_cb($this->m_columns, ', ', __NAMESPACE__ . '\\glueElementDeclarations');
		}
		else
		{
			$qs .= '*';
		}
		
		// table
		$qs .= ' FROM ' . $this->table->expressionString(kExpressionElementDeclaration);
		
		// joins
		foreach ($this->m_joins as $i => $j)
		{
			$qs .= ' ' . $j->expressionString();
		}
		
		// pre computation conditions (where)
		if ($this->m_where->expression() !== null)
		{
			$qs .= ' ' . $this->m_where->expressionString(kExpressionElementName);
		}
		
		// group by
		if (($this->m_group instanceof SelectQueryGroupByStatement))
		{
			$qs .= ' ' . $this->m_group->expressionString($selectColumnAliases);
		}
		
		// post computation conditions (having)
		if ($this->m_having->expression() !== null)
		{
			$qs .= ' ' . $this->m_having->expressionString(kExpressionElementAlias);
		}
		
		// union
		foreach ($this->m_unionQueries as $i => $query)
		{
			$qs .= ' UNION ' . $query->expressionString(self::IS_UNION);
		}
		
		if (!self::isUnion($a_options))
		{
			// order by
			if (($this->m_order instanceof ISelectQueryOrderByStatement))
			{
				$qs .= ' ' . $this->m_order->expressionString($selectColumnAliases);
			}
			
			// limit
			if (($this->m_limit instanceof SelectQueryLimitStatement))
			{
				$qs .= ' ' . $this->m_limit->expressionString();
			}
		}
		return $qs;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->expressionString();
	}

	/**
	 *
	 * @see common/ns/sources/sql/IQuery#execute()
	 * @return RecordSet
	 */
	public function execute()
	{
		$qs = $this->expressionString();
		if (!$qs)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Invalid query string', __FILE__, __LINE__);
		}
		
		$result = $this->m_datasource->executeQuery($qs);
		if ($result)
		{
			return new Recordset($this->datasource, $result);
		}
		return false;
	}

	/**
	 * Add a column in the field list
	 *
	 * @return number of added elements or false if an error occurs
	 */
	public function addColumn(/* ... */)
	{
		$n = func_num_args();
		for($i = 0; $i < $n; $i++)
		{
			$c = func_get_arg($i);
			if (($c instanceof ns\IExpression))
			{
				$this->m_columns [] = $c;
			}
			elseif (is_string($c))
			{
				if (!($c = $this->table->fieldObject($c)))
				{
					return ns\Reporter::error($this, __METHOD__ . '(): Invalid field name', __FILE__, __LINE__);
				}
				$this->m_columns [] = $c;
			}
			else
			{
				return ns\Reporter::error($this, __METHOD__ . '(): Invalid parameter (ns\IExpression or table field name expected)', __FILE__, __LINE__);
			}
		}
		return $n;
	}

	/**
	 *
	 * @param Table $a_table        	
	 * @param int $a_joinType        	
	 * @return ISelectQueryJoin
	 */
	public function createJoin(Table $a_table, $a_joinType = Datasource::kStringKeywordJoinNatural)
	{
		$kw = $a_table->datasource->getDatasourceString($a_joinType);
		if (!(is_string($kw) && strlen($kw) > 0))
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Invalid join type ' . strval($a_joinType), __FILE__, __LINE__);
		}
		
		if ($a_joinType == Datasource::kStringJoinNatural)
		{
			$res = new SelectQueryNaturalJoin($this->table, $a_table);
		}
		else
		{
			$res = new SelectQueryJoin($a_joinType, $this->table, $a_table);
		}
		
		return $res;
	}

	/**
	 * @param SelectQueryJoin $a_oJoin
	 */
	public function addJoin(SelectQueryJoin $a_oJoin)
	{
		/**
		 * @note joins could be from 2 other joined tables
		 *
		 * if ($a_oJoin->leftTable != $this->table || $a_oJoin->rightTable == $this->table)
		 * {
		 * ns\Reporter::fatalError($this, __METHOD__.'(): Invalid tables', __FILE__, __LINE__);
		 * }
		 */
		
		$this->m_joins [] = $a_oJoin;
	}

	/**
	 * Create a LIMIT statement
	 *
	 * @param integer $offset        	
	 * @param integer $limit        	
	 * @return SelectQueryLimitStatement
	 */
	protected function createLimitStatement($offset, $limit)
	{
		$v = new SelectQueryLimitStatement($offset, $limit);
		return $v;
	}

	/**
	 * Set or get limit constraint
	 * 
	 * @param
	 *        	$offset
	 * @param
	 *        	$limit
	 * @return SelectQueryLimitStatement
	 */
	final function limit($offset = null, $limit = null)
	{
		if (($offset instanceof SelectQueryLimitStatement))
		{
			$this->m_limit = $offset;
		}
		elseif (is_numeric($offset) && is_numeric($limit))
		{
			$this->m_limit = $this->createLimitStatement($offset, $limit);
		}
		
		return $this->m_limit;
	}

	/**
	 * Set random orger constraint
	 * @param string $a_value
	 * @return boolean
	 */
	final function randomOrder($a_value = null)
	{
		if ($a_value !== null && $this->m_randomOrder != $a_value)
		{
			$this->m_randomOrder = ($a_value) ? true : false;
			$this->m_order = ($a_value) ? new SelectQueryRandomOrderByStatement() : new SelectQueryOrderByStatement();
		}
		
		return $this->m_randomOrder;
	}

	/**
	 * @param SelectQuery $a_query
	 */
	final function addUnionQuery(SelectQuery $a_query)
	{
		$this->m_unionQueries [] = $a_query;
	}

	/**
	 * Set or get DISTINCT constraint
	 * @param string $a_distinct
	 * @return boolean
	 */
	function distinct($a_distinct = null)
	{
		if ($a_distinct !== null)
		{
			$this->m_distinct = $a_distinct;
		}
		return $this->m_distinct;
	}

	/**
	 * Indicate if query must add the DISTINCT keyword
	 *
	 * @var bool
	 */
	protected $m_distinct;

	/**
	 * Fields
	 *
	 * @var array
	 */
	protected $m_columns;

	/**
	 * Joins
	 *
	 * @var array
	 */
	protected $m_joins;

	/**
	 * WHERE statement
	 */
	protected $m_where;

	/**
	 * HAVING Statement
	 */
	protected $m_having;

	/**
	 * Limit
	 *
	 * @var SelectQueryLimitStatement
	 */
	protected $m_limit;

	/**
	 * Group by clause
	 *
	 * @var SelectQueryGroupByStatement
	 */
	protected $m_group;

	protected $m_order;

	protected $m_randomOrder;

	protected $m_unionQueries;
}

class TruncateQuery extends TableQuery implements ns\IExpression
{

	public function __construct(Table $a_table)
	{
		parent::__construct($a_table);
	}

	public function execute()
	{
		$qs = $this->expressionString();
		if (!$qs)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Invalid query string', __FILE__, __LINE__);
		}
		
		$result = $this->m_datasource->executeQuery($qs);
		if ($result)
		{
			return new Recordset($this->datasource, $result);
		}
		return false;
	}

	public function expressionString($a_options = null)
	{
		return 'TRUNCATE TABLE ' . $this->table->expressionString(kExpressionElementName);
	}
}
