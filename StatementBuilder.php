<?php

namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\ArrayUtil;
use NoreSources\Creole\PreformattedBlock;

/**
 * Build a SQL statement string to be used in a SQL engine 
 *
 */
abstract class StatementBuilder
{

	public function __construct($flags = 0)
	{
		$this->builderFlags = $flags;
		$this->parser = null;
	}

	public function getBuilderFlags()
	{
		return $this->builderFlags;
	}

	abstract function escapeString($value);

	abstract function escapeIdentifier($identifier);

	abstract function getParameter($name);

	/**
	 *
	 * @param integer $joinTypeFlags JOIN type flags
	 * @return string
	 */
	public function getJoinOperator($joinTypeFlags)
	{
		$s = '';
		if (($joinTypeFlags & K::JOIN_NATURAL) == K::JOIN_NATURAL)
			$s .= 'NATURAL ';
		
		if (($joinTypeFlags & K::JOIN_LEFT) == K::JOIN_LEFT)
		{
			$s . 'LEFT ';
			if (($joinTypeFlags & K::JOIN_OUTER) == K::JOIN_OUTER)
			{
				$s .= 'OUTER ';
			}
		}
		else if (($joinTypeFlags & K::JOIN_RIGHT) == K::JOIN_RIGHT)
		{
			$s . 'RIGHT ';
			if (($joinTypeFlags & K::JOIN_OUTER) == K::JOIN_OUTER)
			{
				$s .= 'OUTER ';
			}
		}
		else if (($joinTypeFlags & K::JOIN_CROSS) == K::JOIN_CROSS)
		{
			$s .= 'CROSS ';
		}
		else if (($joinTypeFlags & K::JOIN_INNER) == K::JOIN_INNER)
		{
			$s .= 'INNER ';
		}
		
		return ($s . 'JOIN');
	}

	/**
	 *
	 * @param string $expression
	 * @return \NoreSources\SQL\Expression|\NoreSources\SQL\PreformattedExpression
	 */
	public function parseExpression($expression)
	{
		if ($this->parser instanceof ExpressionParser)
		{
			return $this->parser->parse($expression);
		}
		
		return new PreformattedExpression($expression);
	}

	public function resolveExpressionType(Expression $expression, StructureResolver $resolver)
	{
		$type = $expression->getExpressionDataType();
		if ($type != K::kDataTypeUndefined)
		{
			return $type;
		}
		
		if ($expression instanceof ColumnExpression)
		{
			$column = $resolver->findColumn($expression->path);
			return $column->getProperty(TableColumnStructure::DATA_TYPE);
		}
		else if ($expression instanceof UnaryOperatorExpression)
		{
			$operator = strtolower(trim($expression->operator));
			switch ($operator)
			{
				case 'not':
				case 'is':
					return K::kDataTypeBoolean;
			}
		}
		elseif ($expression instanceof BinaryOperatorExpression)
		{
			$operator = strtolower(trim($expression->operator));
			switch ($operator)
			{
				case '==':
				case '=':
				case '!=':
				case '<>':
					return K::kDataTypeBoolean;
			}
		}
		
		return $type;
	}

	/**
	 * Escape literal value
	 *
	 * @param LiteralExpression $literal
	 * @return string
	 */
	public function getLiteral(LiteralExpression $literal)
	{
		if ($literal->type & K::kDataTypeNumber)
			return $literal->value;
		
		return $this->escapeString($literal->value);
	}

	/**
	 *
	 * @param array $path
	 * @return string
	 */
	public function escapeIdentifierPath($path)
	{
		return ns\ArrayUtil::implode($path, '.', ns\ArrayUtil::IMPLODE_VALUES, array (
				$this,
				'escapeIdentifier' 
		));
	}

	/**
	 *
	 * @param StructureElement $structure
	 * @return string
	 */
	public function getCanonicalName(StructureElement $structure)
	{
		$s = $this->escapeIdentifier($structure->getName());
		$p = $structure->parent();
		while ($p && !($p instanceof DatasourceStructure))
		{
			$s = $this->escapeIdentifier($p->getName()) . '.' . $s;
			$p = $p->parent();
		}
		
		return $s;
	}

	/**
	 *
	 * @param ExpressionParser $parser
	 * @return \NoreSources\SQL\StatementBuilder
	 */
	protected function setExpressionParser(ExpressionParser $parser)
	{
		$this->parser = $parser;
		return $this;
	}

	/**
	 *
	 * @var integer
	 */
	private $builderFlags;

	/**
	 *
	 * Expression parser
	 * @var ExpressionParser
	 */
	private $parser;
}

/**
 * 
 *
 */
class GenericStatementBuilder extends StatementBuilder
{

	public function __construct()
	{
		$this->parameters = new \ArrayObject();
		$this->setExpressionParser(new ExpressionParser());
	}

	public function escapeString($value)
	{
		return "'" . $value . "'";
	}

	public function escapeIdentifier($identifier)
	{
		return '[' . $identifier . ']';
	}

	public function getParameter($name)
	{
		if (!$this->parameters->offsetExists($name))
		{
			$c = $this->parameters->count() + 1;
			$this->parameters->offsetSet($name, $c);
		}
		
		return '$' . $this->parameters->offsetGet($name);
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $parameters;
}