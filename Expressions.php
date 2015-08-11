<?php

/**
 * Copyright © 2012-2015 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * A set of ns\IExpression designed to be used in queries
 * 
 * @package SQL
 */
namespace NoreSources\SQL;
use NoreSources as ns;

require_once ('BasicExpressions.php');

/**
 * IS null expression
 */
class SQLIsNull extends ns\UnaryOperatorExpression
{

	public function __construct(Datasource $a_datasource, $a_bPositive = true)
	{
		$expValue = $a_datasource->createSQLNull();
		if (!$a_bPositive)
		{
			$expValue = new ns\UnaryOperatorExpression('NOT ', $expValue);
			$expValue->protect(false);
		}
		
		parent::__construct('IS ', $expValue);
		$this->protect(false);
	}
}

/**
 * IN (value1, value2, .
 *
 * .) expression
 */
class SQLIn extends ns\UnaryOperatorExpression
{

	public function __construct(Datasource $a_datasource, $a_value, $a_bPositive = true)
	{
		if ($a_value instanceof SelectQuery)
		{
			if ($a_value->columnCount != 1)
			{
				ns\Reporter::warning($this, __METHOD__ . '(): SelectQuery does not have exacty one column', __FILE__, __LINE__);
			}
			$a_value = new SurroundingElementExpression($a_value);
		}
		elseif ($a_value instanceof SQLDataArray)
		{
			if (is_array($a_value))
			{
				$a_value = new SQLDataArray($a_datasource, $a_value);
			}
			else
			{
				ns\Reporter::fatalError($this, __METHOD__ . '(): Invalid parameter', __FILE__, __LINE__);
			}
		}
		
		if (!$a_bPositive)
		{
			$expValue = new ns\UnaryOperatorExpression('IN ', $a_value);
			parent::__construct('NOT ', $expValue);
		}
		else
		{
			parent::__construct('IN ', $a_value);
		}
	}
}

/**
 * A smart database 'equal' depending on values given
 * - IS null, NOT IS null if value is SQLNull
 * - IN, NOT IN if value is an array, a DatabasevalueArray or a select query
 * - = or <> operator otherwise
 */
class SQLSmartEquality extends ns\BinaryOperatorExpression
{

	public function __construct(ns\IExpression $a_column, $a_value, $a_bEqual = true)
	{
		$strOperator = '=';
		
		// force to construct a FormattedData object
		if (!($a_value instanceof SQLData) && !($a_value instanceof SelectQuery) && !($a_value instanceof SQLFunction))
		{
			if ($a_column instanceof TableField)
			{
				$t = $a_column->type();
				if ($t !== null)
				{
					$data = $a_column->datasource->createData($t);
					$data->import($a_value);
					$a_value = $data;
				}
				else
				{
					$a_value = bestEffortImport($a_value, $a_column->datasource);
				}
			}
			else
			{
				ns\Reporter::addWarning($this, __METHOD__ . '(): Argument 1 is not a TableField and argument 2 is not a SQLValue. ' . 'The method will not be able to precisely determine data type.', __FILE__, __LINE__);
				$a_value = bestEffortImport($a_value);
			}
		}
		
		if (($a_value instanceof SQLDataArray) || ($a_value instanceof SelectQuery))
		{
			if ($a_bEqual)
			{
				parent::__construct('IN', $a_column, new SurroundingElementExpression($a_value));
			}
			else
			{
				$in = new SQLIn($a_column->datasource, $a_value, true);
				$in->protect(false);
				parent::__construct('NOT', $a_column, $in);
			}
		}
		elseif ($a_value instanceof SQLNull)
		{
			if ($a_bEqual)
			{
				parent::__construct('IS', $a_column, $a_value);
			}
			else
			{
				$is = new SQLIsNull($a_column->datasource, $a_value, true);
				parent::__construct('NOT', $a_column, $is);
				$this->protect(false);
			}
		}
		else
		{
			if ($a_bEqual)
			{
				parent::__construct('=', $a_column, $a_value);
			}
			else
			{
				parent::__construct('<>', $a_column, $a_value);
			}
		}
		
		$this->protect(false);
	}
}

/**
 * A 'field BETWEEN value1 AND value2' expression
 */
class SQLBetween extends ns\BinaryOperatorExpression
{

	/**
	 *
	 * @param TableField $a_leftExpression        	
	 * @param mixed $a_min
	 *        	basic type Function or SQLValue
	 * @param mixed $a_max
	 *        	basic type Function or SQLValue
	 */
	public function __construct(ns\IExpression $a_leftExpression, $a_min, $a_max)
	{
		parent::__construct('BETWEEN', $a_leftExpression);
		$this->protect(false);
		
		$t = null;
		if ($a_leftExpression instanceof TableField)
		{
			$t = $a_leftExpression->type();
		}
		
		if (!($a_min instanceof ns\IExpression))
		{
			if (is_object($t) && $t instanceof ISQLDataType)
			{
				$a_min = createValue($t, $a_min, $a_leftExpression->datasource);
			}
			else
			{
				var_dump($t);
				ns\Reporter::fatalError($this, 'Invalid min expression (' . var_export($a_min, true) . ')', __FILE__, __LINE__);
			}
		}
		
		if (!($a_max instanceof ns\IExpression))
		{
			if ($t)
			{
				$a_max = createValue($t, $a_max, $a_leftExpression->datasource);
			}
			else
			{
				ns\Reporter::fatalError($this, 'Invalid max expression', __FILE__, __LINE__);
			}
		}
		
		$this->rightExpression(new ns\BinaryOperatorExpression('AND', $a_min, $a_max));
	}
}

/**
 * BETWEEN, <= or >= depending on right expression settings
 */
class AutoInterval extends ns\BinaryOperatorExpression
{

	public function __construct(ns\IExpression $a_leftExpression, $a_min, $a_max)
	{
		parent::__construct(($a_min && $a_max) ? 'BETWEEN' : (($a_min) ? '>= ' : '<='), $a_leftExpression);
		$this->protect(false);
		
		if (!($a_min || $a_max))
		{
			ns\Reporter::fatalError($this, 'Missing min or max parameter', __FILE__, __LINE__);
		}
		
		$t = null;
		if ($a_leftExpression instanceof TableField)
		{
			$t = $a_leftExpression->type();
		}
		
		if ($a_min)
		{
			if (!($a_min instanceof ns\IExpression))
			{
				if ($t)
				{
					$a_min = createValue($t, $a_min, $a_leftExpression->datasource);
				}
				else
				{
					ns\Reporter::fatalError($this, 'Invalid min expression', __FILE__, __LINE__);
				}
			}
		}
		
		if ($a_max)
		{
			if ($t)
			{
				$a_max = createValue($t, $a_max, $a_leftExpression->datasource);
			}
			else
			{
				ns\Reporter::fatalError($this, 'Invalid max expression', __FILE__, __LINE__);
			}
		}
		
		if ($a_min && $a_max)
		{
			$this->rightExpression(new ns\BinaryOperatorExpression('AND', $a_min, $a_max));
		}
		else if ($a_min)
		{
			$this->rightExpression($a_min);
		}
		else
		{
			$this->rightExpression($a_max);
		}
	}
}

?>