<?php

namespace NoreSources\SQL;

use NoreSources as ns;
use Ferno\Loco as Loco;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

/**
 * Comma separated list of Expression
 */
class ListExpression extends \ArrayObject implements Expression
{

	public function __construct($array)
	{
		parent::__construct();
		if (ns\Container::isArray($array))
		{
			$this->exchangeArray($array);
		}
		else
		{
			$this->exchangeArray(ns\Container::createArray($array));
		}
	}

	public function buildExpression(StatementContext $context)
	{
		return ns\Container::implodeValues($this, ', ', function ($v) use ($context)
		{
			return $context->evaluateExpression($v)->buildExpression($context);
		});
	}

	public function getExpressionDataType()
	{
		foreach ($this as $value)
		{
			return $value->getExpressionDataType();
		}

		return K::DATATYPE_UNDEFINED;
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
		foreach ($this as $value)
		{
			$value->traverse($callable, $context, $flags);
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

	public function __construct($operator, Expression $operand, $type = K::DATATYPE_UNDEFINED)
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
		if ($this->type == K::DATATYPE_UNDEFINED)
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
	public function __construct($operator, Expression $left = null, Expression $right = null, $type = K::DATATYPE_UNDEFINED)
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

	public function getExpressionDataType()
	{
		$t = $this->type;
		if ($t == K::DATATYPE_UNDEFINED)
			$t = $this->leftOperand->getExpressionDataType();
		if ($t == K::DATATYPE_UNDEFINED)
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
 * <expressio> IN (<expression-list>)
 * or <expression IN (SelectQuery)
 */
class InOperatorExpression extends ListExpression
{

	/**
	 * @var Expression
	 */
	public $leftOperand;

	/**
	 * @var boolean
	 */
	public $include;

	/**
	 * @param Expression $left
	 * @param array|\ArrayObject $list
	 * @param boolean $include
	 */
	public function __construct(Expression $left = null, $list, $include = true)
	{
		parent::__construct($list);
		$this->leftOperand = $left;
		$this->include = $include;
	}

	public function getExpressionDataType()
	{
		if ($this->leftOperand instanceof Expression)
			return $this->leftOperand->getExpressionDataType();

		return parent::getExpressionDataType();
	}

	public function buildExpression(StatementContext $context)
	{
		$s = $this->leftOperand->buildExpression($context);
		if (!$this->include)
			$s .= ' NOT';
		$s .= ' IN';

		return ($s . '(' . parent::buildExpression($context) . ')');
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		// Respect order
		call_user_func($callable, $this, $context, $flags);

		if ($this->leftOperand instanceof Expression)
			$this->leftOperand->traverse($callable, $context, $flags);

		foreach ($this as $value)
		{
			if ($value instanceof Expression)
			{
				$value->traverse($callable, $context, $flags);
			}
		}
	}

	/**
	 * @var \ArrayObject
	 */
	private $elements;
}

/**
 * a BETWEEN b AND c
 */
class BetweenExpression implements Expression
{

	/**
	 * @var boolean
	 */
	public $inside;

	/**
	 * Left operand
	 * @var Expression
	 */
	public $leftOperand;

	/**
	 * @var Expression
	 */
	public $minBoudary;

	/**
	 * @var Expression
	 */
	public $maxBoundary;

	/**
	 * @param Expression $left Left operand
	 * @param Expression $min Minimum boundary
	 * @param Expression $max Maximum boundary
	 */
	public function __construct(Expression $left = null, Expression $min = null, Expression $max = null)
	{
		$this->inside = true;
		$this->leftOperand = $left;
		$this->minBoudary = $min;
		$this->maxBoundary = $max;
	}

	/**
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Expression::buildExpression()
	 */
	public function buildExpression(StatementContext $context)
	{
		return $this->leftOperand->buildExpression($context) . ($this->inside ? '' : ' NOT') . ' BETWEEN ' . $this->minBoudary->buildExpression($context) . ' AND ' . $this->maxBoudary->buildExpression($context);
	}

	/**
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Expression::getExpressionDataType()
	 */
	public function getExpressionDataType()
	{
		return K::DATATYPE_BOOLEAN;
	}

	/**
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Expression::traverse()
	 */
	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
		$this->leftOperand->traverse($callable, $context, $flags);
		$this->minBoudary->traverse($callable, $context, $flags);
		$this->maxBoudary->traverse($callable, $context, $flags);
	}
}
