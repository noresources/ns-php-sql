<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

//
namespace NoreSources\SQL\Statement\Manipulation;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Expression\TableReference;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Expression\TokenizableExpressionInterface;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Statement\StatementException;
use NoreSources\SQL\Statement\Traits\ColumnValueTrait;
use NoreSources\SQL\Statement\Traits\WhereConstraintTrait;
use NoreSources\SQL\Structure\TableStructure;

/**
 * UPDATE query
 */
class UpdateQuery extends Statement implements \ArrayAccess
{

	use ColumnValueTrait;
	use WhereConstraintTrait;

	/**
	 *
	 * @param NamespaceStructure|string $table
	 */
	public function __construct($table)
	{
		$this->initializeWhereConstraints();

		if ($table instanceof TableStructure)
		{
			$table = $table->getPath();
		}

		$this->table = new TableReference($table);
		$this->columnValues = new \ArrayObject();
	}

	/**
	 *
	 * @param Evaluable $args...
	 *        	Evaluable expression list
	 * @return \NoreSources\SQL\Statement\UpdateQuery
	 */
	public function where()
	{
		return $this->addConstraints($this->whereConstraints, func_get_args());
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
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

			if (!($value instanceof TokenizableExpressionInterface))
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
}
