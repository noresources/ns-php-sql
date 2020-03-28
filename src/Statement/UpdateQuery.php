<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

// Namespace
namespace NoreSources\SQL\Statement;

// Aliases
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\Evaluator as X;
use NoreSources\SQL\Expression\TokenizableExpression;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Expression\TableReference;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContext;
use NoreSources\SQL\Structure\TableStructure;

/**
 * UPDATE query
 */
class UpdateQuery extends Statement implements \ArrayAccess
{

	use ColumnValueTrait;

	/**
	 *
	 * @param TablesetStructure|string $table
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
			if (!($x instanceof TokenizableExpression))
				$x = X::evaluate($x);

			$this->whereConstraints->append($x);
		}

		return $this;
	}

	public function tokenize(TokenStream $stream, TokenStreamContext $context)
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
			->identifier($context->getStatementBuilder()
			->getCanonicalName($tableStructure));

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
			 * @var ColumnStructure $column
			 */

			if (!($value instanceof TokenizableExpression))
			{
				$type = K::DATATYPE_UNDEFINED;
				if ($column->hasColumnProperty(K::COLUMN_DATA_TYPE))
					$type = $column->getColumnProperty(K::COLUMN_DATA_TYPE);

				$value = new Literal($value, $type);
			}

			$stream->identifier($context->getStatementBuilder()
				->escapeIdentifier($columnName))
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
