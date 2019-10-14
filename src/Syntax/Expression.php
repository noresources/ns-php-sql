<?php
namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

interface Expression extends Tokenizable
{

	/**
	 *
	 * @return integer
	 */
	function getExpressionDataType();

	/**
	 *
	 * @param callable $callable
	 *        	Callable with the following prototype:: callable ($expression, StatementContext, $flags)
	 *
	 *        	The expression should call the @c $callable then invoke the @c traverse method of all nested Expression
	 */
	function traverse($callable, StatementContext $context, $flags = 0);
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
	 *
	 * @param integer $keyword
	 */
	public function __construct($keyword)
	{
		$this->keyword = $keyword;
	}

	public function tokenize(TokenStream &$stream, StatementContext $context)
	{
		return $stream->keyword($context->getKeyword($this->keyword));
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

	/**
	 *
	 * @var mixed
	 */
	public $value;

	/**
	 *
	 * @var integer Literal type
	 */
	public $targetType;

	public function __construct($value, $type = K::DATATYPE_STRING)
	{
		$this->value = $value;
		$this->targetType = $type;
	}

	public function tokenize(TokenStream &$stream, StatementContext $context)
	{
		return $stream->literal($context->getLiteral($this->value, $this->targetType));
	}

	public function getExpressionDataType()
	{
		return $this->targetType;
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

	public function tokenize(TokenStream &$stream, StatementContext $context)
	{
		return $stream->parameter($this->name);
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
 * Table column path or Result column aliasden
 */
class ColumnExpression implements Expression
{

	/**
	 *
	 * @var string
	 */
	public $path;

	public function __construct($path)
	{
		$this->path = $path;
	}

	public function tokenize(TokenStream &$stream, StatementContext $context)
	{
		$target = $context->findColumn($this->path);
		if ($target instanceof TableColumnStructure)
		{
			$parts = explode('.', $this->path);
			foreach ($parts as $part)
			{
				if ($context->isAlias($part))
					return $stream->identifier($context->escapeIdentifierPath($parts));
			}

			return $stream->identifier($context->getCanonicalName($target));
		}
		else
			return $stream->identifier($context->escapeIdentifier($this->path));
	}

	/**
	 * Column data type will be resolved by StructureResolver
	 *
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
 *
 * @author renaud
 *
 */
class TableExpression implements Expression
{

	/**
	 *
	 * @var string
	 */
	public $path;

	public function __construct($path)
	{
		$this->path = $path;
	}

	public function tokenize(TokenStream &$stream, StatementContext $context)
	{
		$target = $context->findTable($this->path);

		if ($target instanceof TableStructure)
		{
			$parts = explode('.', $this->path);
			foreach ($parts as $part)
			{
				if ($context->isAlias($part))
					return $stream->identifier($context->escapeIdentifierPath($parts));
			}

			return $stream->identifier($context->getCanonicalName($target));
		}
		else
			return $stream->identifier($context->escapeIdentifier($this->path));
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