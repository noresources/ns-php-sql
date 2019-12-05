<?php
namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

interface Expression extends Tokenizable
{
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
	 * @param mixed $value
	 * @param ColumnPropertyMap|integer $type
	 */
	public function __construct($value, $type = K::DATATYPE_STRING)
	{
		$this->value = $value;
		if ($type instanceof ColumnPropertyMap)
			$this->target = $type;
		elseif (\is_integer($type))
			$this->target = new ArrayColumnPropertyMap([
				K::COLUMN_PROPERTY_DATA_TYPE => $type
			]);
		else
			throw new \InvalidArgumentException(
				ns\TypeDescription::getName($type) . 'is not a valid target argument for ' .
				ns\TypeDescription::getName($this));
	}

	public function tokenize(TokenStream &$stream, StatementContext $context)
	{
		return $stream->literal($context->serializeColumnData($this->target, $this->value));
	}

	public function getExpressionDataType()
	{
		if ($this->target->hasColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE))
			return $this->target->getColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE);
		return K::DATATYPE_UNDEFINED;
	}

	/**
	 *
	 * @var ColumnPropertyMap Literal type
	 */
	private $target;
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
}