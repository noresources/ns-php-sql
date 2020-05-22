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
use NoreSources\SQL\Expression\Evaluator;
use NoreSources\SQL\Expression\Keyword;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Expression\TokenizableExpressionInterface;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Statement\StatementException;
use NoreSources\SQL\Statement\Traits\ColumnValueTrait;
use NoreSources\SQL\Statement\Traits\StatementTableTrait;

/**
 * INSERT query
 */
class InsertQuery extends Statement implements \ArrayAccess
{

	use ColumnValueTrait;
	use StatementTableTrait;

	/**
	 *
	 * @param TableStructure|string $table
	 * @param string $alias
	 *        	Optional table alias
	 */
	public function __construct($table = null, $alias = null)
	{
		if ($table !== null)
			$this->table($table, $alias);
		$this->columnValues = new \ArrayObject();
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		$builderFlags = $context->getStatementBuilder()->getBuilderFlags(K::BUILDER_DOMAIN_GENERIC);
		$builderFlags |= $context->getStatementBuilder()->getBuilderFlags(K::BUILDER_DOMAIN_INSERT);

		$tableStructure = $context->findTable($this->getTable()->path);
		$context->setStatementType(K::QUERY_INSERT);

		/**
		 *
		 * @var TableStructure $tableStructure
		 */

		$context->pushResolverContext($tableStructure);

		$stream->keyword('insert')
			->space()
			->keyword('into')
			->space()
			->identifier($context->getStatementBuilder()
			->getCanonicalName($tableStructure));
		if ($this->getTable()->alias)
		{
			$stream->space()
				->keyword('as')
				->space()
				->expression($this->getTable(), $context);
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

			$columns[] = $context->getStatementBuilder()->escapeIdentifier($columnName);
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

			$values[] = $value;
		}

		if ($c == 0)
		{
			foreach ($tableStructure as $name => $column)
			{
				/**
				 *
				 * @var ColumnStructure $column
				 */

				if ($column->hasColumnProperty(K::COLUMN_DEFAULT_VALUE))
				{
					$c++;
					$columns[] = $context->getStatementBuilder()->escapeIdentifier($name);
					if ($builderFlags & K::BUILDER_INSERT_DEFAULT_KEYWORD)
					{
						$values[] = new Keyword(K::KEYWORD_DEFAULT);
					}
					else
					{
						$x = Evaluator::evaluate(
							$column->getColumnProperty(K::COLUMN_DEFAULT_VALUE));
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
}
