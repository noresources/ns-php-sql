<?php

namespace NoreSources\SQL;

use NoreSources as ns;
use Ferno\Loco as Loco;
use NoreSources\SQL\Constants as K;

interface Expression
{

	/**
	 * @param StatementContext $context
	 * @return string*
	 */
	function buildExpression(StatementContext $context);

	/**
	 * @return integer
	 */
	function getExpressionDataType();

	/**
	 * @param callable $callable Callable with the following prototype:: callable ($expression, StatementContext, $flags)
	 *       
	 *        The expression should call the @c $callable then invoke the @c traverse method of all nested Expression
	 */
	function traverse($callable, StatementContext $context, $flags = 0);
}

/**
 * Preformatted expression
 */
class PreformattedExpression implements Expression
{

	public $expression;

	/**
	 * @param mixed $value
	 * @param integer $type
	 */
	public function __construct($value, $type = K::kDataTypeUndefined)
	{
		$this->expression = $value;
		$this->type = $type;
	}

	function buildExpression(StatementContext $context)
	{
		return $this->expression;
	}

	function getExpressionDataType()
	{
		return $this->type;
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
	}

	private $type;
}

/**
 * Literal
 */
class LiteralExpression implements Expression
{

	public $value;

	public $type;

	public function __construct($value, $type = K::kDataTypeString)
	{
		$this->value = $value;
		$this->type = $type;
	}

	function buildExpression(StatementContext $context)
	{
		return $context->getLiteral($this->value, $this->type);
	}

	function getExpressionDataType()
	{
		return $this->type;
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
	}
}

/**
 * Query parameter
 */
class ParameterExpression implements Expression
{

	public $name;

	public function __construct($name)
	{
		$this->name = $name;
	}

	function buildExpression(StatementContext $context)
	{
		return $context->getParameter($this->name);
	}

	function getExpressionDataType()
	{
		return K::kDataTypeUndefined;
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
	}
}

/**
 * Table column path or Result column aliasden
 */
class ColumnExpression implements Expression
{

	/**
	 * @var string
	 */
	public $path;

	public function __construct($path)
	{
		$this->path = $path;
	}

	function buildExpression(StatementContext $context)
	{
		$target = $context->findColumn($this->path);

		if ($target instanceof TableColumnStructure)
		{
			$parts = explode('.', $this->path);
			foreach ($parts as $part)
			{
				if ($context->isAlias($part))
					return $context->escapeIdentifierPath($parts);
			}

			return $context->getCanonicalName($target);
		}
		else
			return $context->escapeIdentifier($this->path);
	}

	/**
	 * Column data type will be resolved by StructureResolver
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Expression::getExpressionDataType()
	 */
	function getExpressionDataType()
	{
		return K::kDataTypeUndefined;
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
	}
}

/**
 * Table path
 * @author renaud
 *        
 */
class TableExpression implements Expression
{

	/**
	 * @var string
	 */
	public $path;

	public function __construct($path)
	{
		$this->path = $path;
	}

	function buildExpression(StatementContext $context)
	{
		$target = $context->findTable($this->path);

		if ($target instanceof TableStructure)
		{
			$parts = explode('.', $this->path);
			foreach ($parts as $part)
			{
				if ($context->isAlias($part))
					return $context->escapeIdentifierPath($parts);
			}

			return $context->getCanonicalName($target);
		}
		else
			return $context->escapeIdentifier($this->path);
	}

	function getExpressionDataType()
	{
		return K::kDataTypeUndefined;
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
	}
}

/**
 * Function
 */
class FunctionExpression implements Expression
{

	/**
	 * Function name
	 * @var string
	 */
	public $name;

	/**
	 * @var \ArrayObject
	 */
	public $arguments;

	/**
	 * Function return type
	 * @var integer
	 */
	public $returnType;

	public function __construct($name, $arguments = array())
	{
		$this->name = $name;
		$this->returnType = K::kDataTypeUndefined;

		/**
		 * @todo Recognize function and get its return type
		 */

		if (ns\ArrayUtil::isArray($arguments))
		{
			$this->arguments = new \ArrayObject(ns\ArrayUtil::createArray($arguments));
		}
		else
		{
			$this->arguments = new \ArrayObject();
		}
	}

	function buildExpression(StatementContext $context)
	{
		/**
		 * @todo builder function translator
		 */
		$s = $this->name . '(';
		$a = array ();
		foreach ($this->arguments as $arg)
		{
			$o[] = $arg->buildExpression($context);
		}
		return $s . implode(', ', $o) . ')';
	}

	function getExpressionDataType()
	{
		return $this->returnType;
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
		foreach ($this->arguments as $argumnet)
		{
			$argumnet->traverse($callable, $context, $flags);
		}
	}
}

/**
 * Comma-separated expression list
 */
class ListExpression extends \ArrayObject implements Expression
{

	public $separator;

	public function __construct($list = array(), $separator = ', ')
	{
		parent::__construct($list);
		$this->separator = $separator;
	}

	public function buildExpression(StatementContext $context)
	{
		$s = '';
		$first = true;
		foreach ($this as $expression)
		{
			if (!$first)
				$s .= $this->separator;
			$s .= $expression->buildExpression($context);
		}

		return $s;
	}

	function getExpressionDataType()
	{
		$set = false;
		$current = K::kDataTypeUndefined;

		foreach ($this as $expression)
		{
			$t = $expression->getExpressionDataType();
			if ($set && ($t != $current))
			{
				return K::kDataTypeUndefined;
			}

			$set = true;
			$current = $t;
		}

		return $current;
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
		foreach ($this as $expression)
		{
			$expression->traverse($callable, $context, $flags);
		}
	}
}

/**
 */
class ParenthesisExpression implements Expression
{

	/**
	 * @var Expression
	 */
	public $expression;

	public function __construct(Expression $expression)
	{
		$this->expression;
	}

	function buildExpression(StatementContext $context)
	{
		return '(' . $this->expression->buildExpression($context) . ')';
	}

	function getExpressionDataType()
	{
		return $this->expression->getExpressionDataType();
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
		$this->expression->traverse($callable, $context, $flags);
	}
}

/**
 * Unary operator
 */
class UnaryOperatorExpression implements Expression
{

	/**
	 * @var string
	 */
	public $operator;

	/**
	 * @var Expression
	 */
	public $operand;

	public $type;

	public function __construct($operator, Expression $operand, $type = K::kDataTypeUndefined)
	{
		$this->operator = $operator;
		$this->operand = $operand;
		$this->type = $type;
	}

	function buildExpression(StatementContext $context)
	{
		return $this->operator . ' ' . $this->operand->buildExpression($context);
	}

	function getExpressionDataType()
	{
		if ($this->type == K::kDataTypeUndefined)
			return $this->operand->getExpressionDataType();
		return $this->type;
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
		$this->operand->traverse($callable, $context, $flags);
	}
}

/**
 * Binary operator
 */
class BinaryOperatorExpression implements Expression
{

	public $operator;

	/**
	 * @var Expression
	 */
	public $leftOperand;

	/**
	 * @var Expression
	 */
	public $rightOperand;

	public $type;

	/**
	 * @param string $operator
	 * @param Expression $left
	 * @param Expression $right
	 */
	public function __construct($operator, Expression $left = null, Expression $right = null, $type = K::kDataTypeUndefined)
	{
		$this->operator = $operator;
		$this->leftOperand = $left;
		$this->rightOperand = $right;
		$this->type = $type;
	}

	function buildExpression(StatementContext $context)
	{
		return $this->leftOperand->buildExpression($context) . ' ' . $this->operator . ' ' . $this->rightOperand->buildExpression($context);
	}

	function getExpressionDataType()
	{
		$t = $this->type;
		if ($t == K::kDataTypeUndefined)
			$t = $this->leftOperand->getExpressionDataType();
		if ($t == K::kDataTypeUndefined)
			$t = $this->rightOperand->getExpressionDataType();

		return $t;
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
		$this->leftOperand->traverse($callable, $context, $flags);
		$this->rightOperand->traverse($callable, $context, $flags);
	}
}

/**
 * Option of a CASE expression
 */
class CaseOptionExpression
{

	/**
	 * @var Expression
	 */
	public $when;

	/**
	 * @var Expression
	 */
	public $then;

	public function __construct(Expression $when, Expression $then)
	{
		$this->when = $when;
		$this->then = $then;
	}

	function buildExpression(StatementContext $context)
	{
		return 'WHEN ' . $this->when->buildExpression($context) . ' THEN ' . $this->then->buildExpression($context);
	}

	function getExpressionDataType()
	{
		return $this->then->getExpressionDataType();
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
		$this->when->traverse($callable, $context, $flags);
		$this->then->traverse($callable, $context, $flags);
	}
}

/**
 * CASE
 */
class CaseExpression implements Expression
{

	/**
	 * @var Expression
	 */
	public $subject;

	/**
	 * @var \ArrayObject
	 */
	public $options;

	/**
	 * @var Expression
	 */
	public $else;

	public function __construct(Expression $subject)
	{
		$this->subject = $subject;
		$this->options = new \ArrayObject();
		$this->else = null;
	}

	public function buildExpression(StatementContext $context)
	{
		$s = 'CASE ' . $this->subject;
		foreach ($this->options as $option)
		{
			$s .= ' ' . $option->buildExpression($context);
		}

		if ($this->else instanceof Expression)
		{
			$s .= ' ELSE ' . $this->else->buildExpression($context);
		}

		return $s;
	}

	public function getExpressionDataType()
	{
		$set = false;
		$current = K::kDataTypeUndefined;

		foreach ($this->options as $option)
		{
			$t = $option->getExpressionDataType();
			if ($set && ($t != $current))
			{
				return K::kDataTypeUndefined;
			}

			$set = true;
			$current = $t;
		}

		if ($this->else instanceof Expression)
		{
			$t = $this->else->getExpressionDataType();
			if ($set && ($t != $current))
			{
				return K::kDataTypeUndefined;
			}

			$current = $t;
		}

		return $current;
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
		$this->subject->traverse($callable, $context, $flags);
		foreach ($this->options as $option)
		{
			$option->traverse($callable, $context, $flags);
		}
		$this->else->traverse($callable, $context, $flags);
	}
}

