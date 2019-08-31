<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\ArrayUtil;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

class UpdateQuery extends Statement implements \ArrayAccess
{

	public function __construct(TableStructure $structure = null)
	{
		$this->structure = $structure;
		$this->columnValues = new \ArrayObject();
		$this->whereConstraints = new \ArrayObject();
	}

	/**
	 * @property-read \NoreSources\SQL\TableStructure
	 * @param mixed $member
	 * @return \NoreSources\SQL\TableStructure|unknown
	 */
	public function __get($member)
	{
		if ($member == 'structure')
			return $this->structure;

		return $this->structure->$member;
	}

	/**
	 * Set a column value with a basic type
	 * @param string $columnName Table column name
	 * @param mixed $columnValue Column value. Literal type will be the same as the column data type
	 * @throws StatementException
	 * @return \NoreSources\SQL\UpdateQuery
	 */
	public function setLiteral($columnName, $columnValue)
	{
		if (!($this->structure->offsetExists($columnName)))
			throw new StatementException($this, 'Invalid column ' . $columnName);

		$column = $this->structure->offsetGet($columnName);
		$this->columnValues->offsetSet($columnName, X::literal($columnValue, $column->getProperty(K::COLUMN_PROPERTY_DATA_TYPE)));

		return $this;
	}

	/**
	 * Set a column value with a complex expression.
	 * @param string $columnName
	 * @param Evaluable $columnExpression
	 * @return \NoreSources\SQL\UpdateQuery
	 */
	public function set($columnName, $columnExpression)
	{
		$this->offsetSet($columnName, $columnExpression);
		return $this;
	}

	/**
	 * WHERE constraints
	 * @param Evaluable ...
	 */
	public function where()
	{
		$c = func_num_args();
		for ($i = 0; $i < $c; $i++)
			$this->whereConstraints->append(func_get_arg($i));
	}

	/**
	 * Indicates if the given column exists in table structure
	 * @param string $offset column name
	 */
	public function offsetExists($offset)
	{
		return $this->structure->offsetExists($offset);
	}

	/**
	 * Get current column value
	 */
	public function offsetGet($offset)
	{
		if ($this->columnValues->offsetExists($offset))
			return $this->offsetGet($offset);

		if (!$this->structure->offsetExists($offset))
			throw new StatementException($this, 'Invalid column name ' . $offset);

		$column = $this->structure->offsetGet($offset);
		/**
		 * @var TableColumnStructure $column
		 */

		if ($column->hasProperty(TableColumnStructure::DEFAULT_VALUE))
			return $column->getProperty(TableColumnStructure::DEFAULT_VALUE);

		return null;
	}

	/**
	 * Set column value
	 * @param string $opffset Column name
	 * @param Expression $value Column value
	 */
	public function offsetSet($offset, $value)
	{
		if (!$this->structure->offsetExists($offset))
			throw new StatementException($this, 'Invalid column name ' . $offset);

		$this->columnValues->offsetSet($offset, $value);
	}

	public function offsetUnset($offset)
	{
		$this->columnValues->offsetUnset($offset);
	}

	public function buildExpression(StatementContext $context)
	{
		if ($this->columnValues->count() == 0)
		{
			throw new StatementException($this, 'No column value');
		}
				
		$s = 'UPDATE ' . $context->getCanonicalName($this->structure);
		foreach ($this->columnValues as $column => $value) 
		{
			$x = $context->evaluateExpression($value);
			$s .= ' SET ' . $context->escapeIdentifier ($column) . '=' . $x->buildExpression($context);
		}
		
		if ($this->whereConstraints->count())
		{
			$s .= ' WHERE ';
			$c = null;
			foreach ($this->whereConstraints as $constraint)
			{
				$e = $context->evaluateExpression($constraint);
				if ($c instanceof Expression)
					$c = new BinaryOperatorExpression(' AND ', $c, $e);
					else
						$c = $e;
			}
			
			$s .= $c->buildExpression($context);
		}
		
		return $s;
	}

	/**
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Expression::traverse()
	 */
	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
	}

	/**
	 * @var TableStructure
	 */
	private $structure;
	
	/**
	 * 
	 * @var \ArrayObject Associative array where 
	 * keys are column names 
	 * and values are \NoreSources\SQL\Expression
	 */
	private $columnValues;
	
	/**
	 * WHERE conditions
	 * @var \ArrayObject
	 */
	private $whereConstraints;
}
