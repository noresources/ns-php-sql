<?php
namespace NoreSources\SQL;

use NoreSources as ns;
use Ferno\Loco as Loco;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

/**
 * Unary operator
 */
class UnaryOperatorExpression implements Expression
{

	/**
	 *
	 * @var string
	 */
	public $operator;

	/**
	 *
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

	public function tokenize(TokenStream &$stream, StatementContext $context)
	{
		return $stream->text($this->operator)
			->space()
			->expression($this->operand, $context);
	}

	function getExpressionDataType()
	{
		if ($this->type == K::DATATYPE_UNDEFINED)
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
	 *
	 * @var Expression
	 */
	public $leftOperand;

	/**
	 *
	 * @var Expression
	 */
	public $rightOperand;

	public $type;

	/**
	 *
	 * @param string $operator
	 * @param Expression $left
	 * @param Expression $right
	 */
	public function __construct($operator, Expression $left = null, Expression $right = null,
		$type = K::DATATYPE_UNDEFINED)
	{
		$this->operator = $operator;
		$this->leftOperand = $left;
		$this->rightOperand = $right;
		$this->type = $type;
	}

	public function tokenize(TokenStream &$stream, StatementContext $context)
	{
		return $stream->expression($this->leftOperand, $context)
			->space()
			->text($this->operator)
			->space()
			->expression($this->rightOperand, $context);
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
class InOperatorExpression extends ListExpression implements \IteratorAggregate
{

	/**
	 *
	 * @var Expression
	 */
	public $leftOperand;

	/**
	 *
	 * @var boolean
	 */
	public $include;

	/**
	 *
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

	public function tokenize(TokenStream &$stream, StatementContext $context)
	{
		$stream->expression($this->leftOperand, $context)->space();
		if (!$this->include)
			$stream->keyword('not')->space();

		$stream->keyword('in')->text('(');
		parent::tokenize($stream, $context);
		return $stream->text(')');
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
	 *
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
	 *
	 * @var boolean
	 */
	public $inside;

	/**
	 * Left operand
	 *
	 * @var Expression
	 */
	public $leftOperand;

	/**
	 *
	 * @var Expression
	 */
	public $minBoudary;

	/**
	 *
	 * @var Expression
	 */
	public $maxBoundary;

	/**
	 *
	 * @param Expression $left
	 *        	Left operand
	 * @param Expression $min
	 *        	Minimum boundary
	 * @param Expression $max
	 *        	Maximum boundary
	 */
	public function __construct(Expression $left = null, Expression $min = null,
		Expression $max = null)
	{
		$this->inside = true;
		$this->leftOperand = $left;
		$this->minBoudary = $min;
		$this->maxBoundary = $max;
	}

	public function tokenize(TokenStream &$stream, StatementContext $context)
	{
		$stream->expression($this->leftOperand, $context)->space();
		if (!$this->inside)
			$stream->keyword('not')->space();
		return $stream->keyword('between')
			->space()
			->expression($this->minBoudary, $context)
			->space()
			->expression($this->maxBoundary, $context);
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Expression::getExpressionDataType()
	 */
	public function getExpressionDataType()
	{
		return K::DATATYPE_BOOLEAN;
	}

	/**
	 *
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
