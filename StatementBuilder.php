<?php

namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\SQL\Constants as K;
use NoreSources\ContainerUtil;

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
	 * Get the default type name for a given data type
	 * @param integer $dataType \NoreSources\SQL Data type constant
	 * @return string The default Connection type name for the given data type
	 */
	abstract function getColumnTymeName($dataType = K::kDataTypeUndefined);

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
	 * Get the \DateTime timestamp format accepted by the Connection
	 * @return string \DateTime format string
	 */
	public function getTimestampFormat()
	{
		return \DateTime::ISO8601;
	}

	/**
	 * Get a table column description to be used in CREATE TABLE query
	 * @param TableColumnStructure $column
	 * @return string
	 */
	public function getColumnDescription(TableColumnStructure $column)
	{
		$type = $column->getProperty(K::PROPERTY_COLUMN_DATA_TYPE);

		$s = $this->escapeIdentifier($column->getName());

		$s .= ' ' . $this->getColumnTymeName($type);
		if ($column->hasProperty(K::PROPERTY_COLUMN_DATA_SIZE))
		{
			$s .= '(' . $column->getProperty(K::PROPERTY_COLUMN_DATA_SIZE) . ')';
		}

		if (!$column->getProperty(K::PROPERTY_COLUMN_NULL))
		{
			$s .= ' NOT NULL';
		}

		if ($column->hasProperty(K::PROPERTY_COLUMN_DEFAULT_VALUE))
		{
			$s .= ' DEFAULT ' . $this->getLiteral($column->getProperty(K::PROPERTY_COLUMN_DEFAULT_VALUE), $type);
		}

		return $s;
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
	 * @param mixed $value Literal value
	 * @param integer $type Value type
	 * @return string
	 */
	public function getLiteral($value, $type)
	{
		if (ContainerUtil::isArray ($value))
		{
			$dateTimeKeys = array ('date', 'timezone', 'timezone_type');
			$matchingKeys = 0;
			if (ContainerUtil::count($value) == 3)
			{
				foreach ($dateTimeKeys as $key)
				{
					if (in_array ($key, $value))
						$matchingKeys++;
					else break;
				}
				
				if ($matchingKeys == 3)
				{
					$value = \DateTime::__set_state($value);
				}
			}
		}
		
		if ($value instanceof \DateTime)
		{
			if ($type & K::kDataTypeNumber)
				$value = $value->getTimestamp();
			else
			{
				$value = $value->format ($this->getTimestampFormat());
			}
		}
		
		if ($type & K::kDataTypeNumber)
		{
			if ($type & K::kDataTypeInteger == K::kDataTypeInteger)
				$value = intval($value);
			else
				$value = floatval($value);
			return $value;
		}

		return "'" . $this->escapeString($value) . "'";
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
		return str_replace("'", "''", $value);
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
	
	public function getColumnTymeName ($dataType = K::kDataTypeUndefined)
	{
		switch ($dataType) {
			case K::kDataTypeBinary: return 'BLOB';
			case K::kDataTypeBoolean: return 'BOOL';
			case K::kDataTypeInteger: return 'INTEGER';
			case K::kDataTypeNumber:
			case K::kDataTypeFloat: 
				return 'REAL';
			
		}
		
		return 'TEXT';
	}

}