<?php

/**
 * Copyright © 2012-2015 by Renaud Guillard (dev@nore.fr)
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

use \Iterator;
use \ArrayAccess;

/**
 * An expression relative to a database connection
 */
interface IExpression extends ns\IExpression
{

	/**
	 * Return the database connection linked to object.
	 *
	 * Object can contains its own reference or use one of its member reference.
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
	 * @param SQLAlias $alias        	
	 * @return SQLAlias
	 */
	function alias(SQLAlias $alias = null);
}

/**
 * Represents an alias clause (ie 'AS 'aliasName''
 */
class SQLAlias implements IExpression
{

	/**
	 *
	 * @param
	 *        	$Datasource
	 * @param
	 *        	$aliasName
	 * @return unknown_type
	 */
	public function __construct(Datasource $Datasource = null, $aliasName)
	{
		$this->m_datasource = $Datasource;
		$this->m_aliasName = $aliasName;
	}

	public function __toString()
	{
		return $this->expressionString();
	}

	public function expressionString($a_options = null)
	{
		if ($this->m_datasource)
		{
			return $this->m_datasource->encloseElement($this->m_aliasName);
		}
		
		return $this->m_aliasName;
	}

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
	 * @param string $a_function
	 *        	function name
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
		$this->m_parameterListOptions = kExpressionElementAlias;
	}

	public function __toString()
	{
		return $this->expressionString();
	}

	public function parameterListOptions($options = null)
	{
		if ($options !== null)
		{
			$this->m_parameterListOptions = $options;
		}
		
		return $this->m_parameterListOptions;
	}

	/**
	 * This method is read-only in this class
	 */
	public function expression(ns\IExpression &$a_expression = null)
	{
		if ($a_expression !== null)
		{
			ns\Reporter::addWarning($this, __METHOD__ . ': Read only property');
		}
		return $this->m_expression;
	}

	public function expressionString($a_options = null)
	{
		if ($this->m_expression === null)
		{
			ns\Reporter::fatalError($this, __METHOD__ . '(): Invalid expression given', __FILE__, __LINE__);
		}
		
		$res = $this->m_strOperator . '(' . $this->m_expression->expressionString($this->m_parameterListOptions) . ')';
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

	/**
	 * Set or get function alias name
	 *
	 * @param SQLAlias $alias        	
	 */
	public function alias(SQLAlias $alias = null)
	{
		if ($alias !== null)
		{
			$this->m_alias = $alias;
		}
		
		return $this->m_alias;
	}

	public function hasAlias()
	{
		return ($this->m_alias instanceof SQLAlias);
	}

	/**
	 *
	 * @var SQLAlias
	 */
	protected $m_alias;

	protected $m_parameterListOptions;
}

/**
 * AND operator
 */
class SQLAnd extends ns\BinaryOperatorExpression
{

	public function __construct(ns\IExpression $a_left, ns\IExpression $a_right)
	{
		parent::__construct('AND', $a_left, $a_right);
	}

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

	public function __construct(ns\IExpression $a_left, ns\IExpression $a_right)
	{
		parent::__construct('OR', $a_left, $a_right);
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

	public function __construct($a_values)
	{
		parent::__construct('NOT ', $a_values);
	}

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

	public function __construct(ns\IExpression $a_leftExpression, ns\IExpression $a_rightExpression)
	{
		parent::__construct('AS', $a_leftExpression, $a_rightExpression);
		$this->protect(false);
	}

	public function __toString()
	{
		return $this->expressionString();
	}
}

?>