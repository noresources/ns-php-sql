<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\Expression;
use NoreSources\SQL\Expression\Evaluator as X;
use NoreSources\SQL\Expression\Value;

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
		{
			$x = func_get_arg($i);
			if (!($x instanceof Expression))
				$x = X::evaluate($x);

			$this->whereConstraints->append($x);
		}

		return $this;
	}

	public function tokenize(TokenStream &$stream, BuildContext $context)
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

		if ($this->columnValues->count())
			$stream->space()
				->keyword('set')
				->space();

		$index = 0;
		foreach ($this->columnValues as $columnName => $value)
		{
			if (!$tableStructure->offsetExists($columnName))
				throw new StatementException($this, 'Invalid column "' . $columnName . '"');

			if ($index > 0)
				$stream->text(',')->space();

			$column = $tableStructure->offsetGet($columnName);
			/**
			 *
			 * @var TableColumnStructure $column
			 */

			if (!($value instanceof Expression))
			{
				$type = K::DATATYPE_UNDEFINED;
				if ($column->hasColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE))
					$type = $column->getColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE);

				$value = new Value($value, $type);
			}

			$stream->identifier($context->escapeIdentifier($columnName))
				->text('=')
				->expression($value, $context);

			$index++;
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
