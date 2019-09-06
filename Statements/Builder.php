<?php

namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\SQL\Constants as K;

/**
 * Build a SQL statement string to be used in a SQL engine
 */
abstract class StatementBuilder
{

	/**
	 * @param number $flags Builder flags
	 */
	public function __construct($flags = 0)
	{
		$this->builderFlags = $flags;
		$this->evaluator = null;
	}

	/**
	 * @return number
	 */
	public function getBuilderFlags()
	{
		return $this->builderFlags;
	}

	/**
	 * Escape text string to be inserted in a SQL statement.
	 * @param string $value
	 */
	abstract function escapeString($value);

	/**
	 * Escape SQL identifier to be inserted in a SQL statement.
	 * @param string $identifier
	 */
	abstract function escapeIdentifier($identifier);

	/**
	 * Indicates if the given name is a valid parameter name
	 * @param mixed $name
	 */
	abstract function isValidParameterName($name);

	/**
	 * @param mixed $name
	 * @param StatementContext $context
	 */
	abstract function normalizeParameterName($name, StatementContext $context);

	abstract function getParameter($name, $index = -1);

	/**
	 * Get the default type name for a given data type
	 * @param TableColumnStructure $column Column definition
	 * @return string The default Connection type name for the given data type
	 */
	abstract function getColumnTymeName(TableColumnStructure $column);

	/**
	 * Get syntax keyword.
	 * @param integer $keyword
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function getKeyword($keyword)
	{
		switch ($keyword)
		{
			case K::KEYWORD_CURRENT_TIMESTAMP:
				return 'CURRENT_TIMESTAMP';
			case K::KEYWORD_NULL:
				return 'NULL';
		}

		throw new \InvalidArgumentException($keyword);
	}

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
	public function getColumnDescription(TableColumnStructure $column, StatementContext $context)
	{
		$s = $this->escapeIdentifier($column->getName());

		$s .= ' ' . $this->getColumnTymeName($column);
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
			$v = $context->evaluateExpression($column->getProperty(K::COLUMN_PROPERTY_DEFAULT_VALUE));

			$s .= ' DEFAULT ' . $v->buildExpression($context);
		}

		return $s;
	}

	/**
	 * Build a partial SQL statement describing a table constraint in a CREATE TABLE statement.
	 * @param TableStructure $structure
	 * @param TableConstraint $constraint
	 * @return string
	 */
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
				$s .= ns\Container::implodeKeys($constraint->columns, ', ', array (
						$this,
						'escapeIdentifier'
				));
				$s .= ')';
			}
			$s .= ' REFERENCES ' . $this->getCanonicalName($constraint->foreignTable);
			if ($constraint->count())
			{
				$s .= ' (';
				$s .= ns\Container::implodeValues($constraint->columns, ', ', array (
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

	/**
	 * Find the kind of data returned by the given expression.
	 * @param Expression $expression
	 * @param StructureResolver $resolver
	 * @return number|boolean|number|NULL|string|string
	 */
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
		if ($type == K::DATATYPE_NULL)
			return $this->getKeyword(K::KEYWORD_NULL);
		
		if (ns\Container::isArray($value))
		{
			$dateTimeKeys = array (
					'date',
					'timezone',
					'timezone_type'
			);
			$matchingKeys = 0;
			if (ns\Container::count($value) == 3)
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
		return ns\Container::implode($path, '.', ns\Container::IMPLODE_VALUES, array (
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
	 * GET the SQL keyword associated to the given foreign key action
	 * @param string $action
	 * @return string
	 */
	private function getForeignKeyAction($action)
	{
		switch ($action)
		{
			case K::FOREIGN_KEY_ACTION_CASCADE:
				return 'CASCADE';
			case K::FOREIGN_KEY_ACTION_RESTRICT:
				return 'RESTRICT';
			case K::FOREIGN_KEY_ACTION_SET_DEFAULT:
				return 'SET DEFAULT';
			case K::FOREIGN_KEY_ACTION_SET_NULL:
				'SET NULL';
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

	public function __construct($flags = 0)
	{
		parent::__construct($flags);
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

	function getColumnTymeName(TableColumnStructure $column)
	{
		$dataType = K::DATATYPE_UNDEFINED;
		if ($column->hasProperty(K::COLUMN_PROPERTY_DATA_TYPE))
			$dataType = $column->getProperty(K::COLUMN_PROPERTY_DATA_TYPE);
		
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