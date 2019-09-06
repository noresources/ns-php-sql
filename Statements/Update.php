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

	/**
	 * @param TableSetStructure|string $table
	 * @param string $alias
	 */
	public function __construct($table, $alias = null)
	{
		if ($table instanceof TableStructure)
		{
			$table = $table->getPath();
		}

		$this->table = new TableReference($table, $alias);
		$this->columnValues = new \ArrayObject();
		$this->whereConstraints = new \ArrayObject();
	}

	/**
	 * @param string $columnName
	 * @param mixed $columnValue
	 * @param boolean $evaluable If @c true, the value will be evaluated at build stage. Otherwise, the value is considered as a
	 *        literal of the same type as the column data type.
	 * @return \NoreSources\SQL\UpdateQuery
	 */
	public function set($columnName, $columnValue, $evaluable = true)
	{
		$this->columnValues->offsetSet($columnName, [
				'value' =>$columnValue,
				'evaluate' => $evaluable
		]);
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

		return $this;
	}

	/**
	 * @param string Column name
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return $this->columnValues->offsetExists($offset);
	}

	/**
	 * Get current column value
	 * @param string Column name
	 *       
	 * @return mixed Column current value or @c null if not set
	 */
	public function offsetGet($offset)
	{
		if ($this->columnValues->offsetExists($index))
			return $this->columnValues[$offset]['value'];
		return null;
	}

	/**
	 * @param string $offset Column name
	 * @param Evaluable $value Column value
	 */
	public function offsetSet($offset, $value)
	{
		$this->set($offset, $value, true);
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

		$tableStructure = $context->findTable($this->table->path);
		/**
		 * @var TableStructure $tableStructure
		 */

		$s = 'UPDATE ' . $context->getCanonicalName($tableStructure);
		foreach ($this->columnValues as $columnName => $value)
		{
			if (!$tableStructure->offsetExists($columnName))
				throw new StatementException($this, 'Invalid column "' . $columnName . '"');
			
			$column = $tableStructure->offsetGet($columnName);
			/**
			 * @var TableColumnStructure $column
			 */
			
			$x = null;
			$v = $value['value'];
			if ($v instanceof Expression)
			{
				$x = $v;
			}
			elseif ($value['evaluate'])
			{
				$x = $context->evaluateExpression($v);
			}
			else
			{
				$t = K::DATATYPE_UNDEFINED;
				if ($column->hasProperty(K::COLUMN_PROPERTY_DATA_TYPE))
					$t = $column->getProperty(K::COLUMN_PROPERTY_DATA_TYPE);
				
				$x = new LiteralExpression($v, $t);
			}
			
			$s .= ' SET ' . $context->escapeIdentifier($columnName) . '=' . $x->buildExpression($context);
		}

		if ($this->whereConstraints->count())
		{
			$s .= ' WHERE ' . $context->buildConstraintExpression($this->whereConstraints);
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
		foreach ($this->columnValues as $column => $value)
		{
			if ($value['value'] instanceof Expression)
				call_user_func($callable, $value, $context, $flags);
		}
	}

	/**
	 * @var TableReference
	 */
	private $table;
	
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
