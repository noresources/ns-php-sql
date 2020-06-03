<?php

/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 * A set of \NoreSources\IExpression designed to be used in queries
 *
 * @package SQL
 */
namespace NoreSources\SQL;

require_once ('base.php');

interface DatasourceAwareInterface
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
 * An expression relative to a data source connection
 *
 * @deprecated Remove this
 */
interface IExpression extends \NoreSources\IExpression, DatasourceAwareInterface
{
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
 * Override expressionString() method of \NoreSources\UnaryOperatorExpression
 * - ignore postfixed argument
 * - ignore protect argument (always true)
 * - function name is glued to parenthesis
 *
 * @todo change alias name management
 */
class SQLFunction extends \NoreSources\UnaryOperatorExpression implements IAliasable
{

	/**
	 *
	 * @var integer Parameter list options to use while calling @c expressionString()
	 */
	public $parameterListOptions;

	/**
	 *
	 * @param string $functionName
	 *        	function name
	 * @param mixed $a_values
	 */
	public function __construct($functionName, $a_values = null)
	{
		parent::__construct($functionName);
		$this->m_expression = new \NoreSources\ParameterListExpression();

		if ($a_values instanceof \NoreSources\IExpression)
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
	public function expression(\NoreSources\IExpression &$a_expression = null)
	{
		if (!is_null($a_expression))
			throw new \BadMethodCallException('Read only property');

		return $this->m_expression;
	}

	/**
	 *
	 * @return string
	 */
	public function expressionString($a_options = null)
	{
		if (is_null($this->m_expression))
			throw new \RuntimeException('Initialization error');

		if ($this->m_alias instanceof Alias &&
			(($a_options & kExpressionElementDeclaration) == kExpressionElementAlias))
			return $this->m_alias->expressionString($a_options);

		$res = $this->m_strOperator . '(' .
			$this->m_expression->expressionString($this->parameterListOptions) . ')';
		if ($this->m_alias &&
			(($a_options & kExpressionElementDeclaration) == kExpressionElementDeclaration))
		{
			$res .= ' AS ' . $this->m_alias->expressionString($a_options);
		}
		return $res;
	}

	/**
	 * Add function parameter
	 *
	 * @param \NoreSources\IExpression $a_paramter
	 */
	public function addParameter(\NoreSources\IExpression $a_paramter)
	{
		$this->m_expression->add($a_paramter);
	}

	public function clearParameters()
	{
		$this->m_expression = new \NoreSources\ParameterListExpression();
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
class SQLAnd extends \NoreSources\BinaryOperatorExpression
{

	/**
	 *
	 * @param \NoreSources\IExpression $a_left
	 *        	Left operand
	 * @param \NoreSources\IExpression $a_right
	 *        	Right operand
	 */
	public function __construct(\NoreSources\IExpression $a_left, \NoreSources\IExpression $a_right)
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
class SQLOr extends \NoreSources\BinaryOperatorExpression
{

	/**
	 *
	 * @param \NoreSources\IExpression $a_left
	 *        	Left operand
	 * @param \NoreSources\IExpression $a_right
	 *        	Right operand
	 */
	public function __construct(\NoreSources\IExpression $a_left, \NoreSources\IExpression $a_right)
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
 * NOT operator
 */
class SQLNot extends \NoreSources\UnaryOperatorExpression
{

	/**
	 *
	 * @param unknown $a_values
	 *        	Eleent to negate
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
class SQLAs extends \NoreSources\BinaryOperatorExpression
{

	/**
	 *
	 * @param \NoreSources\IExpression $a_leftExpression
	 *        	Element
	 * @param \NoreSources\IExpression $a_rightExpression
	 *        	Element alias
	 */
	public function __construct(\NoreSources\IExpression $a_leftExpression,
		\NoreSources\IExpression $a_rightExpression)
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
