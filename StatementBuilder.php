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
	abstract function getColumnTymeName($dataType = K::DATATYPE_UNDEFINED);

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
		$type = $column->getProperty(K::COLUMN_PROPERTY_DATA_TYPE);

		$s = $this->escapeIdentifier($column->getName());

		$s .= ' ' . $this->getColumnTymeName($type);
		if ($column->hasProperty(K::COLUMN_PROPERTY_DATA_SIZE))
		{
			$s .= '(' . $column->getProperty(K::COLUMN_PROPERTY_DATA_SIZE) . ')';
		}

		if (!$column->getProperty(K::COLUMN_PROPERTY_NULL))
		{
			$s .= ' NOT NULL';
		}

		if ($column->hasProperty(K::COLUMN_PROPERTY_DEFAULT_VALUE))
		{
			$s .= ' DEFAULT ' . $this->getLiteral($column->getProperty(K::COLUMN_PROPERTY_DEFAULT_VALUE), $type);
		}

		return $s;
	}

	public function getTableConstraintDescription(TableStructure $structure, TableConstraint $constraint)
	{
		$s = '';
		if (strlen($constraint->constraintName))
		{
			$s .= 'CONSTRAINT ' . $this->escapeIdentifier($constraint->constraintName) . ' ';
		}

		if ($constraint instanceof ColumnTableConstraint)
		{
			if ($constraint instanceof PrimaryKeyTableConstraint)
				$s .= 'PRIMARY KEY';
			elseif ($constraint instanceof UniqueTableConstraint)
				$v .= 'UNIQUE';
			$columns = array ();

			foreach ($constraint as $column)
			{
				$columns[] = $this->escapeIdentifier($column->getName());
			}

			$s .= ' (' . implode(', ', $columns) . ')';
		}
		elseif ($constraint instanceof ForeignKeyTableConstraint)
		{
			$s .= 'FOREIGN KEY';
			if ($constraint->count())
			{
				$s .= ' (';
				$s .= ContainerUtil::implodeKeys($constraint->columns, ', ', array (
						$this,
						'escapeIdentifier'
				));
				$s .= ')';
			}
			$s .= ' REFERENCES ' . $this->getCanonicalName($constraint->foreignTable);
			if ($constraint->count())
			{
				$s .= ' (';
				$s .= ContainerUtil::implodeValues($constraint->columns, ', ', array (
						$this,
						'escapeIdentifier'
				));
				$s .= ')';
			}

			if ($constraint->onUpdate)
			{
				$s .= ' ON UPDATE ' . $this->getForeignKeyAction($constraint->onUpdate);
			}

			if ($constraint->onDelete)
			{
				$s .= ' ON DELETE ' . $this->getForeignKeyAction($constraint->onDelete);
			}
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
		if ($type != K::DATATYPE_UNDEFINED)
		{
			return $type;
		}

		if ($expression instanceof ColumnExpression)
		{
			$column = $resolver->findColumn($expression->path);
			return $column->getProperty(TableColumnStructure::DATATYPE);
		}
		else if ($expression instanceof UnaryOperatorExpression)
		{
			$operator = strtolower(trim($expression->operator));
			switch ($operator)
			{
				case 'not':
				case 'is':
					return K::DATATYPE_BOOLEAN;
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
					return K::DATATYPE_BOOLEAN;
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
		if (ContainerUtil::isArray($value))
		{
			$dateTimeKeys = array (
					'date',
					'timezone',
					'timezone_type'
			);
			$matchingKeys = 0;
			if (ContainerUtil::count($value) == 3)
			{
				foreach ($dateTimeKeys as $key)
				{
					if (in_array($key, $value))
						$matchingKeys++;
					else
						break;
				}

				if ($matchingKeys == 3)
				{
					$value = \DateTime::__set_state($value);
				}
			}
		}

		if ($value instanceof \DateTime)
		{
			if ($type & K::DATATYPE_NUMBER)
				$value = $value->getTimestamp();
			else
			{
				$value = $value->format($this->getTimestampFormat());
			}
		}

		if ($type & K::DATATYPE_NUMBER)
		{
			if ($type & K::DATATYPE_INTEGER == K::DATATYPE_INTEGER)
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

	private function getForeignKeyAction($action)
	{
		switch ($action) {
			case K::FOREIGN_KEY_ACTION_CASCADE: return 'CASCADE';
			case K::FOREIGN_KEY_ACTION_RESTRICT: return 'RESTRICT';
			case K::FOREIGN_KEY_ACTION_SET_DEFAULT: return 'SET DEFAULT';
			case K::FOREIGN_KEY_ACTION_SET_NULL: 'SET NULL';
		}
		return 'NO ACTION';
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
	
	public function getColumnTymeName ($dataType = K::DATATYPE_UNDEFINED)
	{
		switch ($dataType) {
			case K::DATATYPE_BINARY: return 'BLOB';
			case K::DATATYPE_BOOLEAN: return 'BOOL';
			case K::DATATYPE_INTEGER: return 'INTEGER';
			case K::DATATYPE_NUMBER:
			case K::DATATYPE_FLOAT: 
				return 'REAL';
			
		}
		
		return 'TEXT';
	}

}