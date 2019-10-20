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

	use ColumnValueTrait;

	/**
	 *
	 * @param TableSetStructure|string $table
	 */
	public function __construct($table)
	{
		if ($table instanceof TableStructure)
		{
			$table = $table->getPath();
		}

		$this->table = new TableReference($table);
		$this->columnValues = new \ArrayObject();
		$this->whereConstraints = new \ArrayObject();
	}

	/**
	 * WHERE constraints
	 *
	 * @param
	 *        	Evaluable ...
	 */
	public function where()
	{
		$c = func_num_args();
		for ($i = 0; $i < $c; $i++)
			$this->whereConstraints->append(func_get_arg($i));

		return $this;
	}

	public function tokenize(TokenStream &$stream, StatementContext $context)
	{
		if ($this->columnValues->count() == 0)
		{
			throw new StatementException($this, 'No column value');
		}

		$tableStructure = $context->findTable($this->table->path);
		$context->pushResolverContext($tableStructure);
		$context->setStatementType(K::QUERY_UPDATE);
		/**
		 *
		 * @var TableStructure $tableStructure
		 */

		$stream->keyword('update')
			->space()
			->identifier($context->getCanonicalName($tableStructure));

		foreach ($this->columnValues as $columnName => $value)
		{
			if (!$tableStructure->offsetExists($columnName))
				throw new StatementException($this, 'Invalid column "' . $columnName . '"');

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

			$stream->space()
				->keyword('set')
				->space()
				->identifier($context->escapeIdentifier($columnName))
				->text('=')
				->expression($x, $context);
		}

		if ($this->whereConstraints->count())
		{
			$stream->space()
				->keyword('where')
				->space()
				->constraints($this->whereConstraints, $context);
		}

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
	 * WHERE conditions
	 *
	 * @var \ArrayObject
	 */
	private $whereConstraints;
}
