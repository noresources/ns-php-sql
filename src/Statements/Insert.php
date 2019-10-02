<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

class InsertQuery extends Statement implements \ArrayAccess
{

	public function __construct($table, $alias = null)
	{
		if ($table instanceof TableStructure)
		{
			$table = $table->getPath();
		}

		$this->table = new TableReference($table);
		$this->columnValues = new \ArrayObject();
	}

	/**
	 *
	 * @param string $columnName
	 * @param mixed $columnValue
	 * @param boolean $evaluate
	 *        	If @c true, the value will be evaluated at build stage. Otherwise, the value is considered as a
	 *        	literal of the same type as the column data type..
	 *        	If @c null, the
	 * @return \NoreSources\SQL\UpdateQuery
	 */
	public function set($columnName, $columnValue, $evaluate = null)
	{
		if ($evaluate === false)
		{
			if ($columnValue instanceof Evaluable)
			{
				throw new \BadMethodCallException(
					'Column value is an Evaluable but $evaluate = false');
			}
		}

		if ($evaluate === null)
		{
			$evaluate = ($columnValue instanceof Evaluable) || (ns\Container::isArray($columnValue));
		}
		
		$this->columnValues->offsetSet($columnName, [
				'value' =>$columnValue,
				'evaluate' => $evaluate
		]);
		return $this;
	}

	/**
	 * Set a column value with an evaluable value
	 *
	 * @param
	 *        	string Column name
	 * @param
	 *        	Evaluable Evaluable expression
	 *        	
	 * @throws \BadMethodCallException
	 * @throws \InvalidArgumentException
	 */
	public function __invoke()
	{
		$args = func_get_args();
		if (count($args) != 2)
			throw new \BadMethodCallException(__CLASS__ . ' invokation expects exactly 2 arguments');

		if (!\is_string($args[0]))
			throw new \InvalidArgumentException(__CLASS__ . '() first argument expects string');

		$this->set($args[0], $args[1], true);
	}

	/**
	 *
	 * @param
	 *        	string Column name
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return $this->columnValues->offsetExists($offset);
	}

	/**
	 * Get current column value
	 *
	 * @param
	 *        	string Column name
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
	 *
	 * @param string $offset
	 *        	Column name
	 * @param mixed $value
	 *        	Column value.
	 */
	public function offsetSet($offset, $value)
	{
		$evaluate = false;
		if ($value instanceof Evaluable)
			$evaluate = true;

		$this->set($offset, $value, $evaluate);
	}

	public function offsetUnset($offset)
	{
		$this->columnValues->offsetUnset($offset);
	}

	public function tokenize(TokenStream &$stream, StatementContext $context)
	{
		$builderFlags = $context->getBuilderFlags(K::BUILDER_DOMAIN_GENERIC);
		$builderFlags |= $context->getBuilderFlags(K::BUILDER_DOMAIN_INSERT);

		$tableStructure = $context->findTable($this->table->path);

		/**
		 *
		 * @var TableStructure $tableStructure
		 */

		$context->pushResolverContext($tableStructure);

		$stream->keyword('insert')
			->space()
			->keyword('into')
			->space()
			->identifier($context->getCanonicalName($tableStructure));
		if ($this->table->alias)
		{
			$stream->space()
				->keyword('as')
				->space()
				->identifier($context->escapeIdentifier($this->table->alias));
		}

		$columns = [];
		$values = [];
		$c = $this->columnValues->count();

		if (($c == 0) && ($builderFlags & K::BUILDER_INSERT_DEFAULT_VALUES))
		{
			$stream->space()->keyword('DEFAULT VALUES');
			$context->popResolverContext();
			return $stream;
		}

		foreach ($this->columnValues as $columnName => $value)
		{
			if (!$tableStructure->offsetExists($columnName))
				throw new StatementException($this, 'Invalid column "' . $columnName . '"');

			$columns[] = $context->escapeIdentifier($columnName);
			$column = $tableStructure->offsetGet($columnName);
			/**
			 *
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
				$x = ExpressionEvaluator::evaluate($v);
			}
			else
			{
				$t = K::DATATYPE_UNDEFINED;
				if ($column->hasProperty(K::COLUMN_PROPERTY_DATA_TYPE))
					$t = $column->getProperty(K::COLUMN_PROPERTY_DATA_TYPE);

				$x = new LiteralExpression($v, $t);
			}

			$values[] = $x;
		}

		if ($c == 0)
		{
			foreach ($tableStructure as $name => $column)
			{
				/**
				 *
				 * @var TableColumnStructure $column
				 */

				if ($column->hasProperty(K::COLUMN_PROPERTY_DEFAULT_VALUE))
				{
					$c++;
					$columns[] = $context->escapeIdentifier($name);
					if ($builderFlags & K::BUILDER_INSERT_DEFAULT_KEYWORD)
					{
						$values[] = new KeywordExpression(K::KEYWORD_DEFAULT);
					}
					else
					{
						$x = ExpressionEvaluator::evaluate(
							$column->getProperty(K::COLUMN_PROPERTY_DEFAULT_VALUE));
						$values[] = $x;
					}
				}
			}
		}

		if ($c == 0)
			throw new StatementException($this, 'No column value');

		$stream->space()->text('(');
		$c = 0;
		foreach ($columns as $column)
		{
			if ($c)
				$stream->text(',')->space();
			$stream->identifier($column);
			$c++;
		}

		$stream->text(')')
			->space()
			->keyword('VALUES')
			->space()
			->text('(');
		$c = 0;
		foreach ($values as $value)
		{
			if ($c)
				$stream->text(',')->space();

			$stream->expression($value, $context);
			$c++;
		}

		$stream->text(')');

		$context->popResolverContext();
		return $stream;
	}

	/**
	 *
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
	 *
	 * @var TableReference
	 */
	private $table;

	/**
	 *
	 * @var \ArrayObject Associative array where
	 *      keys are column names
	 *      and values are \NoreSources\SQL\Expression
	 */
	private $columnValues;
}
