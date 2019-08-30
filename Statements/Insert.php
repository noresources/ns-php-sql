<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\ArrayUtil;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

class InsertQuery extends Statement implements \ArrayAccess
{

	public function __construct(TableStructure $structure = null)
	{
		$this->structure = $structure;
		$this->columnValues = new \ArrayObject();
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
	 * @param mixed $columnValue Column value.
	 *        Literal type will be the same as the column data type
	 * @throws StatementException
	 * @return \NoreSources\SQL\InsertQuery
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
	 * @return \NoreSources\SQL\InsertQuery
	 */
	public function set($columnName, $columnExpression)
	{
		$this->offsetSet($columnName, $columnExpression);
		return $this;
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
		$s = 'INSERT INTO ' . $context->getCanonicalName($this->structure);
		$columns = array ();
		$values = array ();
		$c = $this->columnValues->count();

		if (($c == 0) && ($context->getBuilderFlags() & K::BUILDER_INSERT_DEFAULT_VALUES))
		{
			$s .= ' DEFAULT VALUES';
			return $s;
		}

		foreach ($this->columnValues as $column => $value)
		{
			$columns[] = $context->escapeIdentifier($column);
			$x = $context->evaluateExpression($value);
			$values[] = $x->buildExpression($context);
		}

		if ($c == 0)
		{
			foreach ($this->structure as $name => $column)
			{
				/**
				 * @var TableColumnStructure $column
				 */

				if ($column->hasProperty(K::COLUMN_PROPERTY_DEFAULT_VALUE))
				{
					$c++;
					$columns[] = $context->escapeIdentifier($name);
					if ($context->getBuilderFlags() & K::BUILDER_INSERT_DEFAULT_KEYWORD)
					{
						$values[] = 'DEFAULT';
					}
					else
					{

						$x = $context->evaluateExpression($column->getProperty(K::COLUMN_PROPERTY_DEFAULT_VALUE));
						$values[] = $x->buildExpression($context);
					}
				}
			}
		}

		if ($c == 0)
			throw new StatementException($this, 'No column value');

		return $s . '(' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')';
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
}
