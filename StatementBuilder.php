<?php

namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\ArrayUtil;
use NoreSources\Creole\PreformattedBlock;
use NoreSources\SQL\Constants as K;

/**
 * Build a SQL statement string to be used in a SQL engine
 */
abstract class StatementBuilder
{

	public function __construct($flags = 0)
	{
		$this->builderFlags = $flags;
		$this->evaluator = null;
	}

	public function getBuilderFlags()
	{
		return $this->builderFlags;
	}

	abstract function escapeString($value);

	abstract function escapeIdentifier($identifier);

	abstract function isValidParameterName($name);

	abstract function normalizeParameterName($name, StatementContext $context);

	abstract function getParameter($name, $index = -1);

	/**
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
	 * @param string $expression
	 * @return \NoreSources\SQL\Expression|\NoreSources\SQL\PreformattedExpression
	 */
	public function evaluateExpression($expression)
	{
		if ($this->evaluator instanceof ExpressionEvaluator)
		{
			return $this->evaluator->evaluate($expression);
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
	 * @param ExpressionEvaluator $evaluator
	 * @return \NoreSources\SQL\StatementBuilder
	 */
	protected function setExpressionEvaluator(ExpressionEvaluator $evaluator)
	{
		$this->evaluator = $evaluator;
		return $this;
	}

	/**
	 * @var integer
	 */
	private $builderFlags;

	/**
	 *
	 * Expression evaluator
	 * @var ExpressionEvaluator
	 */
	private $evaluator;
}

/**
 */
class GenericStatementBuilder extends StatementBuilder
{	
	public function __construct()
	{
		$this->parameters = new \ArrayObject();
		$this->setExpressionEvaluator(new ExpressionEvaluator());
	}

	public function escapeString($value)
	{
		return "'" . $value . "'";
	}

	public function escapeIdentifier($identifier)
	{
		return '[' . $identifier . ']';
	}

	public function isValidParameterName($name)
	{
		return true;
	}

	public function normalizeParameterName($name, StatementContext $context)
	{
		return $name;
	}

	public function getParameter($name, $index = -1)
	{
		return '$' . $name;
	}

}