<?php

/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
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

	/**
	 *
	 * @param Table $table
	 */
	public function __construct(Table $table)
	{
		parent::__construct($table);
		$this->columnValues = array ();
	}

	public function __toString()
	{
		return $this->expressionString();
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \NoreSources\SQL\IQuery::execute()
	 *
	 * @return boolean
	 */
	public function execute($flags = 0)
	{
		$qs = $this->expressionString();
		if (!$qs)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Invalid query string', __FILE__, __LINE__);
		}
		
		$result = $this->datasource->executeQuery($qs);
		if ($result)
		{
			return new InsertQueryResult($this->table, $result);
		}
		
		return false;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \NoreSources\IExpression::expressionString()
	 *
	 * @return string
	 */
	public function expressionString($a_options = null)
	{
		if (count($this->columnValues) == 0)
		{
			return false;
		}
		
		$qs = 'INSERT INTO ' . $this->table->expressionString(kExpressionElementName);
		
		$qs .= ' (' . ns\ArrayUtil::implodeKeys(', ', $this->columnValues);
		$qs .= ') VALUES(' . ns\ArrayUtil::implodeValues(', ', $this->columnValues) . ')';
		
		return $qs;
	}

	/**
	 * Add a field value
	 *
	 * @param mixed $a_column string(name) or TableColumn
	 * @param ns\IExpression $a_value
	 */
	public function addColumnValue($a_column, ns\IExpression $a_value)
	{
		$a_column = mixedToTableColumn($a_column, $this->table);
		if (!$a_column)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Unable to get TableColumn object', __FILE__, __LINE__);
		}
		
		$f = $this->datasource->encloseElement($a_column->name);
		
		$this->columnValues[$f] = $a_value->expressionString();
	}

	/**
	 * Add multiple field values
	 *
	 * @param mixed $a_columnAndValues associative array [field name => value]
	 *       
	 *        Values are formatted using TableColumn::importData()
	 */
	public function addColumnValues($a_columnAndValues)
	{
		foreach ($a_columnAndValues as $k => $v)
		{
			$k = mixedToTableColumn($k, $this->table);
			if (!$k)
			{
				ns\Reporter::error($this, __METHOD__ . '(): Invalid field ', __FILE__, __LINE__);
				continue;
			}
			
			$this->addColumnValue($k, $k->importData($v));
		}
	}

	/**
	 * Clear columns
	 */
	public function clear()
	{
		$this->columnValues = array ();
	}

	/**
	 * Field values
	 *
	 * @var associative array(formatted field name => formatted value)
	 */
	protected $columnValues;
}

/**
 * Update query builder
 */
class UpdateQuery extends TableQuery implements ns\IExpression
{

	/**
	 *
	 * @param Table $table
	 */
	public function __construct(Table $table)
	{
		parent::__construct($table);
		$this->columnValues = array ();
	}

	public function __toString()
	{
		return $this->expressionString();
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \NoreSources\SQL\TableQuery::__get()
	 *
	 * @return \NoreSources\SQL\WhereQueryConditionStatement
	 */
	public function __get($member)
	{
		if ($member == 'where')
		{
			if (is_null($this->m_condition))
			{
				$this->m_condition = new WhereQueryConditionStatement();
			}
			
			return $this->m_condition;
		}
		
		return parent::__get($member);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \NoreSources\SQL\IQuery::execute()
	 *
	 * @return \NoreSources\SQL\UpdateQueryResult
	 */
	public function execute($flags = 0)
	{
		$qs = $this->expressionString();
		if (!$qs)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Invalid query string', __FILE__, __LINE__);
		}
		
		$result = $this->datasource->executeQuery($qs);
		if ($result)
		{
			return new UpdateQueryResult($this->datasource, $result);
		}
		return false;
	}

	/**
	 *
	 * @param string $k
	 * @param string $v
	 * @return string
	 */
	public static function glueSetStatements($k, $v)
	{
		return ' ' . $k . '=' . $v;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \NoreSources\IExpression::expressionString()
	 *
	 * @return string
	 */
	public function expressionString($a_options = null)
	{
		/*
		 * if (count($this->columnValues) == 0) { return
		 * ns\Reporter::error($this, __METHOD__.': No field set.'); }
		 */
		$qs = 'UPDATE ' . $this->table->expressionString(kExpressionElementName) . ' SET ';
		$qs .= ' ' . ns\ArrayUtil::implode(', ', $this->columnValues, array (
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
	 * @param mixed $a_column string(name) or TableColumn
	 * @param ns\IExpression $a_value
	 */
	public function addColumnValue($a_column, ns\IExpression $a_value)
	{
		$a_column = mixedToTableColumn($a_column, $this->table);
		if (!(is_object($a_column) && ($a_column instanceof TableColumn)))
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Unable to get TableColumn object', __FILE__, __LINE__);
		}
		
		$f = $this->datasource->encloseElement($a_column->name);
		
		$this->columnValues[$f] = $a_value->expressionString();
		
		return true;
	}

	/**
	 * Add multiple field values
	 *
	 * @param mixed $a_columnAndValues associative array [field name => value]
	 *       
	 *        Values are formatted using TableColumn::importData()
	 */
	public function addColumnValues($a_columnAndValues)
	{
		foreach ($a_columnAndValues as $k => $v)
		{
			$k = mixedToTableColumn($k, $this->table);
			if (!$k)
			{
				ns\Reporter::error($this, __METHOD__ . '(): Invalid field ', __FILE__, __LINE__);
				continue;
			}
			$this->addColumnValue($k, $k->importData($v));
		}
	}

	/**
	 *
	 * @param $a_cond ns\IExpression
	 * @return \NoreSources\SQL\WhereQueryConditionStatement
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
	 * @var associative array(formatted field name => formatted value)
	 */
	protected $columnValues;
}

class SelectQueryStaticValueColumn implements ns\IExpression
{

	/**
	 *
	 * @param \NoreSources\SQL\SelectQuery $a_query
	 * @param mixed $a_value
	 * @param string $a_strAlias
	 */
	public function __construct(SelectQuery $a_query, $a_value, $a_strAlias)
	{
		$this->m_oQuery = $a_query;
		$this->m_oValue = (($a_value instanceof ns\IExpression) ? $a_value : new FormattedData($a_value));
		$this->m_alias = (is_string($a_strAlias) && strlen($a_strAlias)) ? new Alias($a_query->datasource, $a_strAlias) : null;
	}

	/**
	 *
	 * @return string
	 */
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
	 * @param string $a_strOperator Operator
	 * @param ns\IExpression $a_oExpression Expression representing the condition(s)
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
		
		if (is_callable($this->m_expression, 'protect'))
		{
			$this->m_expression->protect = false;
		}
		
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
		if (!($this->m_expression instanceof ns\IExpression))
		{
			$this->m_expression = $a_oExpression;
		}
		else
		{
			$this->m_expression = new SQLOr($this->m_expression, $a_oExpression);
		}
		
		if (is_callable($this->m_expression, 'protect'))
		{
			$this->m_expression->protect = false;
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
		$this->protect = false;
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
		$this->protect = false;
	}
}

class DeleteQuery extends TableQuery
{

	public function __construct(Table $table)
	{
		parent::__construct($table);
		$this->m_condition = new WhereQueryConditionStatement();
	}

	public function __toString()
	{
		return $this->expressionString();
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

	/**
	 *
	 * @return \NoreSources\SQL\DeleteQueryResult
	 */
	public function execute($flags = 0)
	{
		$qs = $this->expressionString();
		if (!$qs)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Invalid query string', __FILE__, __LINE__);
		}
		
		$result = $this->datasource->executeQuery($qs);
		if ($result !== false)
		{
			return new DeleteQueryResult($this->datasource, $result);
		}
		return false;
	}

	/**
	 *
	 * @param integer $a_options
	 * @return string
	 */
	public function expressionString($a_options = null)
	{
		$qs = 'DELETE FROM ' . $this->table->expressionString(kExpressionElementDeclaration);
		
		if (!is_null($this->where->expression()))
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
	 *
	 * {@inheritdoc}
	 *
	 * @see \NoreSources\IExpression::expressionString()
	 * @return string
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
	 * Call self::expressionString() method with default parameter @return
	 * string
	 *
	 * @return string
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
		$this->datasource = $a_datasource;
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
	 *
	 * @param mixed $a_options Array of preformatted SELECT column names(or alias if any)
	 * @return string
	 */
	public function expressionString($a_options = null)
	{
		if (count($this->m_columns))
		{
			return 'GROUP BY ' . ns\ArrayUtil::implode(', ', $this->m_columns, array (
					get_class($this),
					'glueGroupByStatement' 
			), $a_options);
		}
		
		return '';
	}

	/**
	 * Add a column in the field list
	 */
	public function addColumn(/* ... */)
	{
		$n = func_num_args();
		for ($i = 0; $i < $n; $i++)
		{
			$c = func_get_arg($i);
			if (($c instanceof Alias) || ($c instanceof ITableColumn))
			{
				$this->m_columns[] = $c;
			}
			elseif (($c instanceof IAliasable) && $c->hasAlias())
			{
				$this->m_columns[] = $c->alias();
			}
			elseif (is_string($c))
			{
				$this->m_columns[] = new Alias($this->datasource, $c);
			}
		}
		return $n;
	}

	public function clear()
	{
		$this->m_columns = array ();
	}

	protected $m_columns;

	protected $datasource;
}

interface ISelectQueryOrderByStatement extends ns\IExpression
{}

/**
 * Random order
 *
 * @todo this feature may not exists in all DB systems
 */
class SelectQueryRandomOrderByStatement implements ISelectQueryOrderByStatement
{

	public function __construct()
	{}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \NoreSources\IExpression::expressionString()
	 * @return string
	 */
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

	/**
	 *
	 * @param unknown $k
	 * @param unknown $v
	 * @param unknown $selectColumns
	 * @return string
	 */
	public static function glueOrderByStatement($k, $v, $selectColumns = null)
	{
		if (is_array($selectColumns))
		{
			$alias = $v[0]->expressionString(kExpressionElementAlias);
			if (in_array($alias, $selectColumns))
			{
				return $alias . ' ' . (($v[1]) ? 'ASC' : 'DESC');
			}
		}
		
		return $v[0]->expressionString(kExpressionElementName) . ' ' . (($v[1]) ? 'ASC' : 'DESC');
	}

	/**
	 * Implementation of ns\IExpression method
	 * @return string
	 */
	public function expressionString($a_options = null)
	{
		if (count($this->m_columns))
		{
			return 'ORDER BY ' . ns\ArrayUtil::implode(', ', $this->m_columns, array (
					get_class($this),
					'glueOrderByStatement' 
			), $a_options);
		}
		
		return '';
	}

	public function clear()
	{
		$this->m_columns = array ();
	}

	/**
	 *
	 * @param ns\IExpression $a_column
	 * @param boolean $a_bAsc
	 */
	public function addColumn(ns\IExpression $a_column, $a_bAsc = true)
	{
		$this->addOrder($a_column, $a_bAsc);
	}

	/**
	 * Add a column in the field list
	 */
	public function addOrder(ns\IExpression $a_column, $a_bAsc = true)
	{
		$this->m_columns[] = array (
				$a_column,
				$a_bAsc 
		);
	}

	protected $m_columns;
}

abstract class ISelectQueryJoin extends ns\UnaryOperatorExpression
{

	/**
	 *
	 * @param integer $a_joinType
	 * @param Table $a_leftTable
	 * @param Table $a_rightTable
	 */
	public function __construct($a_joinType, Table $a_leftTable, Table $a_rightTable)
	{
		parent::__construct($a_leftTable->datasource->getDatasourceString($a_joinType), $a_rightTable);
		$this->m_leftTable = $a_leftTable;
		$this->m_rightTable = $a_rightTable;
		$this->m_joinType = $a_joinType;
		$this->protect = false;
	}

	/**
	 *
	 * @param string $member
	 * @throws \InvalidArgumentException
	 * @return \NoreSource\SQL\Datasource|\NoreSources\SQL\Table
	 */
	public function __get($member)
	{
		if ($member == 'datasource')
		{
			$this->m_leftTable->datasource;
		}
		elseif ($member == 'joinType')
		{
			return $this->m_joinType;
		}
		elseif ($member == 'leftTable')
		{
			return $this->m_leftTable;
		}
		elseif ($member == 'rightTable')
		{
			return $this->m_rightTable;
		}
		
		return parent::__get($member);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \NoreSources\UnaryOperatorExpression::expressionString()
	 * @return string
	 */
	public function expressionString($a_options = null)
	{
		return parent::expressionString(kExpressionElementDeclaration);
	}

	/**
	 *
	 * @var integer
	 */
	protected $m_joinType;

	/**
	 *
	 * @var Table
	 */
	protected $m_leftTable;

	/**
	 *
	 * @var Table
	 */
	protected $m_rightTable;
}

class SelectQueryNaturalJoin extends ISelectQueryJoin
{

	/**
	 *
	 * @param Table $a_leftTable
	 * @param Table $a_rightTable
	 */
	public function __construct(Table $a_leftTable, Table $a_rightTable)
	{
		parent::__construct(kJoinNatural, $a_leftTable, $a_rightTable);
	}
}

class SelectQueryJoin extends ISelectQueryJoin
{

	/**
	 *
	 * @param unknown $a_joinType
	 * @param Table $a_leftTable
	 * @param Table $a_rightTable
	 */
	public function __construct($a_joinType, Table $a_leftTable, Table $a_rightTable)
	{
		parent::__construct($a_joinType, $a_leftTable, $a_rightTable);
		$this->m_joinLink = null;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \NoreSources\SQL\ISelectQueryJoin::expressionString()
	 * @return string
	 */
	public function expressionString($a_options = null)
	{
		return parent::expressionString() . ($this->m_joinLink ? ' ' . $this->m_joinLink->expressionString(kExpressionElementName) : '');
	}

	/**
	 *
	 * @param TableColumn $a_leftField
	 * @param TableColumn $a_rightField
	 * @return boolean
	 */
	public function addLink(TableColumn $a_leftField, TableColumn $a_rightField)
	{
		$e = new ns\BinaryOperatorExpression('=', $a_leftField, $a_rightField);
		$e->protect = false;
		
		if (is_null($this->m_joinLink))
		{
			$this->m_joinLink = new ns\UnaryOperatorExpression('ON', $e);
		}
		else
		{
			$and = new SQLAnd($this->m_joinLink->expression(), $e);
			$and->protect = false;
			$this->m_joinLink->expression($and);
		}
		
		$this->m_joinLink->protect = true;
	}

	protected $m_joinLink;
}

/**
 * SELECT query generator
 */
class SelectQuery extends TableQuery implements ns\IExpression
{
	
	public $distinct;

	/**
	 * Constructor
	 *
	 * @param Datasource $a_oDatasource Connection to a data source
	 * @param Table $table Main table of the SELECT query
	 */
	public function __construct(Table $table)
	{
		parent::__construct($table);
		$this->distinct = false;
		$this->m_having = new HavingQueryConditionStatement();
		$this->m_where = new WhereQueryConditionStatement();
		$this->m_unionQueries = array ();
		$this->m_joins = array ();
		$this->m_columns = array ();
		$this->m_randomOrder = false;
	}

	/**
	 *
	 * @return SelectQuery
	 */
	public function __clone()
	{
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
		
		$this->table = clone $this->table;
	}

	public function __toString()
	{
		return $this->expressionString();
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @param $member string
	 * @return array|integer|\NoreSources\SQL\SelectQueryGroupByStatement|\NoreSources\SQL\SelectQueryLimitStatement|\NoreSources\SQL\SelectQueryOrderByStatement|\NoreSources\SQL\WhereQueryConditionStatement|\NoreSources\SQL\HavingQueryConditionStatement
	 */
	public function __get($member)
	{
		if ($member == 'columns')
		{
			return $this->m_columns;
		}
		elseif ($member == 'columnCount')
		{
			return count($this->m_columns);
		}
		elseif ($member == 'groupBy')
		{
			if (!$this->m_group)
			{
				$this->m_group = new SelectQueryGroupByStatement($this->datasource);
			}
			
			return $this->m_group;
		}
		elseif ($member == 'orderBy')
		{
			if (!$this->m_order)
			{
				$this->m_order = new SelectQueryOrderByStatement();
			}
			
			return $this->m_order;
		}
		elseif ($member == 'where')
		{
			if (!$this->m_where)
			{
				$this->m_where = new WhereQueryConditionStatement();
			}
			
			return $this->m_where;
		}
		elseif ($member == 'having')
		{
			if (!$this->m_having)
			{
				$this->m_having = new HavingQueryConditionStatement();
			}
			
			return $this->m_having;
		}
		
		return parent::__get($member);
	}

	public function __call($member, $arguments)
	{
		if ($member == 'limit')
		{
			if (count($arguments))
				return call_user_func_array(array (
						$this,
						'setLimit' 
				), $arguments);
			else
				return parent::__get('limit');
		}
		
		return parent::__call($member, $arguments);
	}

	// Inherited methods
	/**
	 *
	 * @return string
	 */
	public function expressionString($a_options = null)
	{
		$qs = 'SELECT ';
		if ($this->distinct) $qs .= 'DISTINCT ';
		
		// columns
		$selectColumnAliases = array ();
		if (count($this->m_columns))
		{
			foreach ($this->m_columns as $c)
			{
				$selectColumnAliases[] = $c->expressionString(kExpressionElementAlias);
			}
			
			$qs .= ns\ArrayUtil::implode($this->m_columns, ', ', __NAMESPACE__ . '\\glueElementDeclarations');
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
		
		// pre computation conditions(where)
		if (!is_null($this->m_where->expression()))
		{
			$qs .= ' ' . $this->m_where->expressionString(kExpressionElementName);
		}
		
		// group by
		if (($this->m_group instanceof SelectQueryGroupByStatement))
		{
			$qs .= ' ' . $this->m_group->expressionString($selectColumnAliases);
		}
		
		// post computation conditions(having)
		if (!is_null($this->m_having->expression()))
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
	 *
	 * @return RecordSet
	 */
	public function execute($flags = kRecordsetFetchBoth)
	{
		$qs = $this->expressionString();
		if (!$qs)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Invalid query string', __FILE__, __LINE__);
		}
		
		$result = $this->datasource->executeQuery($qs);
		if ($result)
		{
			return new Recordset($this->datasource, $result, ($flags & kRecordsetFetchBoth));
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
		for ($i = 0; $i < $n; $i++)
		{
			$c = func_get_arg($i);
			if (($c instanceof ns\IExpression))
			{
				$this->m_columns[] = $c;
			}
			elseif (is_string($c))
			{
				if (!($c = $this->table->getColumn($c)))
				{
					return ns\Reporter::error($this, __METHOD__ . '(): Invalid field name', __FILE__, __LINE__);
				}
				$this->m_columns[] = $c;
			}
			else
			{
				return ns\Reporter::error($this, __METHOD__ . '(): Invalid parameter(ns\IExpression or table field name expected)', __FILE__, __LINE__);
			}
		}
		return $n;
	}

	/**
	 *
	 * @param Table $table
	 * @param string $a_joinType
	 * @return \NoreSources\SQL\SelectQueryJoin
	 */
	public function createJoin(Table $table, $a_joinType = kJoinNatural)
	{
		$kw = $table->datasource->getDatasourceString($a_joinType);
		if (!(is_string($kw) && strlen($kw) > 0))
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Invalid join type ' . strval($a_joinType), __FILE__, __LINE__);
		}
		
		if ($a_joinType == kJoinNatural)
		{
			$res = new SelectQueryNaturalJoin($this->table, $table);
		}
		else
		{
			$res = new SelectQueryJoin($a_joinType, $this->table, $table);
		}
		
		return $res;
	}

	/**
	 *
	 * @param \NoreSources\SQL\ISelectQueryJoin $a_oJoin
	 */
	public function addJoin(ISelectQueryJoin $a_oJoin)
	{
		/**
		 * @note joins could be from 2 other joined tables
		 *
		 * if ($a_oJoin->leftTable != $this->table || $a_oJoin->rightTable ==
		 * $this->table)
		 * {
		 * ns\Reporter::fatalError($this, __METHOD__.'(): Invalid tables',
		 * __FILE__, __LINE__);
		 * }
		 */
		$this->m_joins[] = $a_oJoin;
	}

	/**
	 * Create a LIMIT statement
	 *
	 * @param integer $offset
	 * @param integer $limit
	 * @return \NoreSources\SQL\SelectQueryLimitStatement
	 */
	protected function createLimitStatement($offset, $limit)
	{
		$v = new SelectQueryLimitStatement($offset, $limit);
		return $v;
	}

	/**
	 * Set or get limit constraint
	 *
	 * @param integer $offset
	 * @param integer $limit
	 * @return \NoreSources\SQL\SelectQueryLimitStatement
	 */
	final function setLimit($offset = null, $limit = null)
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
	 * Set random order constraint
	 *
	 * @param string $a_value
	 * @return boolean
	 */
	final function randomOrder($a_value = null)
	{
		if (!is_null($a_value) && $this->m_randomOrder != $a_value)
		{
			$this->m_randomOrder = ($a_value) ? true : false;
			$this->m_order = ($a_value) ? new SelectQueryRandomOrderByStatement() : new SelectQueryOrderByStatement();
		}
		
		return $this->m_randomOrder;
	}

	/**
	 *
	 * @param \NoreSources\SQL\SelectQuery $a_query
	 */
	final function addUnionQuery(SelectQuery $a_query)
	{
		$this->m_unionQueries[] = $a_query;
	}

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

	public function __construct(Table $table)
	{
		parent::__construct($table);
	}

	public function __toString()
	{
		return $this->expressionString();
	}

	/**
	 *
	 * @return boolean
	 */
	public function execute($flags = 0)
	{
		$qs = $this->expressionString();
		if (!$qs)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Invalid query string', __FILE__, __LINE__);
		}
		
		$result = $this->datasource->executeQuery($qs);
		if ($result)
		{
			return true;
		}
		return false;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \NoreSources\IExpression::expressionString()
	 * @return string
	 */
	public function expressionString($a_options = null)
	{
		return 'TRUNCATE TABLE ' . $this->table->expressionString(kExpressionElementName);
	}
}
