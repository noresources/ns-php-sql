<?php

/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 * A set of ns\IExpression designed to be used in queries
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources as ns;

require_once ('base.php');
require_once (NS_PHP_PATH . '/core/MathExpressions.php');

/**
 * An expression relative to a data source connection
 */
interface IExpression extends ns\IExpression
{

	/**
	 * Return the data source connection linked to object.
	 *
	 * Object can contains its own reference or use one of its member reference.
	 *
	 * @return Datasource
	 */
	function getDatasource();
}

/**
 * SQL statement element which can be referenced by an alias
 */
interface IAliasable
{

	/**
	 *
	 * @return bool
	 */
	function hasAlias();

	/**
	 * Set and get element alias
	 *
	 * @param Alias $alias
	 * @return Alias
	 */
	function alias(Alias $alias = null);
}

/**
 * Represents an alias clause (ie 'AS 'aliasName''
 */
class Alias implements IExpression
{

	/**
	 *
	 * @param $Datasource
	 * @param $aliasName
	 * @return unknown_type
	 */
	public function __construct(Datasource $Datasource = null, $aliasName)
	{
		$this->m_datasource = $Datasource;
		$this->m_aliasName = $aliasName;
	}

	/**
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->expressionString();
	}

	/**
	 *
	 * @return string
	 */
	public function expressionString($a_options = null)
	{
		if ($this->m_datasource)
		{
			return $this->m_datasource->encloseElement($this->m_aliasName);
		}
		
		return $this->m_aliasName;
	}

	/**
	 *
	 * @return string
	 */
	public function getAliasName()
	{
		return $this->m_aliasName;
	}

	/**
	 *
	 * @return Datasource
	 */
	public function getDatasource()
	{
		return $this->m_datasource;
	}

	/**
	 *
	 * @var string
	 */
	protected $m_aliasName;

	/**
	 *
	 * @var Datasource
	 */
	protected $m_datasource;
}

class_alias(__NAMESPACE__ . '\Alias', __NAMESPACE__ . '\SQLAlias');

/**
 * A unary operator that represents a SQL function
 * *
 * Override expressionString() method of ns\UnaryOperatorExpression
 * - ignore postfixed argument
 * - ignore protect argument (always true)
 * - function name is glued to parenthesis
 *
 * @todo change alias name management
 */
class SQLFunction extends ns\UnaryOperatorExpression implements IAliasable
{

	/**
	 *
	 * @var integer Parameter list options to use while calling @c expressionString()
	 */
	public $parameterListOptions;

	/**
	 *
	 * @param string $a_function function name
	 * @param mixed $a_values
	 */
	public function __construct($a_function, $a_values = null)
	{
		parent::__construct($a_function);
		$this->m_expression = new ns\ParameterListExpression();
		
		if ($a_values instanceof ns\IExpression)
		{
			$this->m_expression->add($a_values);
		}
		
		$this->m_alias = null;
		$this->parameterListOptions = kExpressionElementAlias;
	}

	/**
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->expressionString();
	}

	/**
	 * This method is read-only in this class
	 */
	public function expression(ns\IExpression &$a_expression = null)
	{
		if (!is_null($a_expression))
		{
			ns\Reporter::addWarning($this, __METHOD__ . ': Read only property');
		}
		return $this->m_expression;
	}

	/**
	 *
	 * @return string
	 */
	public function expressionString($a_options = null)
	{
		if (is_null($this->m_expression))
		{
			ns\Reporter::fatalError($this, __METHOD__ . '(): Invalid expression given', __FILE__, __LINE__);
		}
		
		$res = $this->m_strOperator . '(' . $this->m_expression->expressionString($this->parameterListOptions) . ')';
		if ($this->m_alias && (($a_options & kExpressionElementDeclaration) == kExpressionElementDeclaration))
		{
			$res .= ' AS ' . $this->m_alias->expressionString($a_options);
		}
		return $res;
	}

	/**
	 * Add function parameter
	 *
	 * @param ns\IExpression $a_paramter
	 */
	public function addParameter(ns\IExpression $a_paramter)
	{
		$this->m_expression->add($a_paramter);
	}

	public function clearParameters()
	{
		$this->m_expression = null;
	}
	
	/**
	 * Set or get function alias name
	 *
	 * @param Alias $alias
	 */
	public function alias(Alias $alias = null)
	{
		if (!is_null($alias))
		{
			$this->m_alias = $alias;
		}
		
		return $this->m_alias;
	}

	/**
	 *
	 * @return bool
	 */
	public function hasAlias()
	{
		return ($this->m_alias instanceof Alias);
	}

	/**
	 *
	 * @var Alias
	 */
	protected $m_alias;
}

/**
 * AND operator
 */
class SQLAnd extends ns\BinaryOperatorExpression
{

	/**
	 *
	 * @param ns\IExpression $a_left Left operand
	 * @param ns\IExpression $a_right Right operand
	 */
	public function __construct(ns\IExpression $a_left, ns\IExpression $a_right)
	{
		parent::__construct('AND', $a_left, $a_right);
	}

	/**
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->expressionString();
	}
}

/**
 * OR operator
 */
class SQLOr extends ns\BinaryOperatorExpression
{

	/**
	 *
	 * @param ns\IExpression $a_left Left operand
	 * @param ns\IExpression $a_right Right operand
	 */
	public function __construct(ns\IExpression $a_left, ns\IExpression $a_right)
	{
		parent::__construct('OR', $a_left, $a_right);
	}

	/**
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->expressionString();
	}
}

/**
 * DISTINCT column specifier
 */
class Distinct extends ns\UnaryOperatorExpression
{

	public function __construct($a_column)
	{
		parent::__construct('DISTINCT', $a_column);
		$this->protect = false;
	}

	public function __toString()
	{
		return $this->expressionString();
	}
}

/**
 * NOT operator
 */
class SQLNot extends ns\UnaryOperatorExpression
{

	/**
	 *
	 * @param unknown $a_values Eleent to negate
	 */
	public function __construct($a_values)
	{
		parent::__construct('NOT ', $a_values);
	}

	/**
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->expressionString();
	}
}

/**
 * AS operator (a AS b)
 */
class SQLAs extends ns\BinaryOperatorExpression
{

	/**
	 *
	 * @param ns\IExpression $a_leftExpression Element
	 * @param ns\IExpression $a_rightExpression Element alias
	 */
	public function __construct(ns\IExpression $a_leftExpression, ns\IExpression $a_rightExpression)
	{
		parent::__construct('AS', $a_leftExpression, $a_rightExpression);
		$this->protect = false;
	}

	/**
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->expressionString();
	}
}
