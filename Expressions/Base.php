<?php

namespace NoreSources\SQL;

use NoreSources as ns;
use Ferno\Loco as Loco;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

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
	public function __construct($value, $type = K::DATATYPE_UNDEFINED)
	{
		$this->expression = $value;
		$this->type = $type;
	}

	public function buildExpression(StatementContext $context)
	{
		return $this->expression;
	}

	public function getExpressionDataType()
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
 * SQL Language keyword.
 *
 * For all keyword that may differ from a SGBD to another.
 */
class KeywordExpression implements Expression
{

	/**
	 * Keyword constant.
	 * One of Constants\KEYWORD_*.
	 *
	 * @var integer
	 */
	public $keyword;

	/**
	 * @param integer $keyword
	 */
	public function __construct($keyword)
	{
		$this->keyword = $keyword;
	}

	public function buildExpression(StatementContext $context)
	{
		return $context->getKeyword($this->keyword);
	}

	public function getExpressionDataType()
	{
		return K::DATATYPE_UNDEFINED;
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
	}
}

/**
 * Literal
 */
class LiteralExpression implements Expression
{

	public $value;

	public $type;

	public function __construct($value, $type = K::DATATYPE_STRING)
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
		return K::DATATYPE_UNDEFINED;
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
		return K::DATATYPE_UNDEFINED;
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

	public function buildExpression(StatementContext $context)
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

	public function getExpressionDataType()
	{
		return K::DATATYPE_UNDEFINED;
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
	}
}