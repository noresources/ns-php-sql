<?php

namespace NoreSources\SQL;

use NoreSources as ns;
use Ferno\Loco as Loco;
use NoreSources\SQL\Constants as K;

interface Expression
{

	/**
	 * @param StatementBuilder $builder
	 * @param StructureResolver $resolver
	 * @return string
	 */
	function buildExpression(StatementBuilder $builder, StructureResolver $resolver);

	/**
	 * @return integer
	 */
	function getExpressionDataType();
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

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		return $this->expression;
	}

	function getExpressionDataType()
	{
		return $this->type;
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

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		return $builder->getLiteral($this);
	}

	function getExpressionDataType()
	{
		return $this->type;
	}
}

/**
 * Query parameter
 *
 */
class ParameterExpression implements Expression
{

	public $name;

	public function __construct($name)
	{
		$this->name = $name;
	}

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		return $builder->getParameter($this->name);
	}

	function getExpressionDataType()
	{
		return K::kDataTypeUndefined;
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

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		$target = $resolver->findColumn($this->path);

		if ($target instanceof TableColumnStructure)
		{
			$parts = explode('.', $this->path);
			foreach ($parts as $part)
			{
				if ($resolver->isAlias($part))
					return $builder->escapeIdentifierPath($parts);
			}

			return $builder->getCanonicalName($target);
		}
		else
			return $builder->escapeIdentifier($this->path);
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

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		$target = $resolver->findTable($this->path);

		if ($target instanceof TableStructure)
		{
			$parts = explode('.', $this->path);
			foreach ($parts as $part)
			{
				if ($resolver->isAlias($part))
					return $builder->escapeIdentifierPath($parts);
			}

			return $builder->getCanonicalName($target);
		}
		else
			return $builder->escapeIdentifier($this->path);
	}

	function getExpressionDataType()
	{
		return K::kDataTypeUndefined;
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

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		/**
		 * @todo builder function translator
		 */
		$s = $this->name . '(';
		$a = array ();
		foreach ($this->arguments as $arg)
		{
			$o[] = $arg->buildExpression($builder, $resolver);
		}
		return $s . implode(', ', $o) . ')';
	}

	function getExpressionDataType()
	{
		return $this->returnType;
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

	public function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		$s = '';
		$first = true;
		foreach ($this as $expression)
		{
			if (!$first)
				$s .= $this->separator;
			$s .= $expression->buildExpression($builder, $resolver);
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
}

/**
 * 
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

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		return '(' . $this->expression->buildExpression($builder, $resolver) . ')';
	}

	function getExpressionDataType()
	{
		return $this->expression->getExpressionDataType();
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

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		return $this->operator . ' ' . $this->operand->buildExpression($builder, $resolver);
	}

	function getExpressionDataType()
	{
		if ($this->type == K::kDataTypeUndefined)
			return $this->operand->getExpressionDataType();
		return $this->type;
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

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		return $this->leftOperand->buildExpression($builder, $resolver) . ' ' . $this->operator . ' ' . $this->rightOperand->buildExpression($builder, $resolver);
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

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		return 'WHEN ' . $this->when->buildExpression($builder, $resolver) . ' THEN ' . $this->then->buildExpression($builder, $resolver);
	}

	function getExpressionDataType()
	{
		return $this->then->getExpressionDataType();
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

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		$s = 'CASE ' . $this->subject;
		foreach ($this->options as $option)
		{
			$s .= ' ' . $option->buildExpression($builder, $resolver);
		}

		if ($this->else instanceof Expression)
		{
			$s .= ' ELSE ' . $this->else->buildExpression($builder, $resolver);
		}

		return $s;
	}

	function getExpressionDataType()
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
}

