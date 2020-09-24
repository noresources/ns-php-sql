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
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\TableStructure;

/**
 * INSERT query
 */
class InsertQuery extends Statement implements \ArrayAccess
{

	use ColumnValueTrait;
	use StatementTableTrait;

	/**
	 * Alias of table() method
	 *
	 * @param TableStructure|string $table
	 *        	Table structure path.
	 * @return \NoreSources\SQL\Statement\Manipulation\InsertQuery
	 */
	public function into($table)
	{
		return $this->table($table);
	}

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

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getStatementBuilder()->getPlatform();

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
			->identifier(
			$context->getStatementBuilder()
				->getPlatform()
				->quoteIdentifierPath($tableStructure));
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

		$hasDefaultValues = $platform->queryFeature(
			[
				K::PLATFORM_FEATURE_INSERT,
				K::PLATFORM_FEATURE_DEFAULTVALUES
			], false);

		$hasDefaultKeyword = $platform->queryFeature(
			[
				K::PLATFORM_FEATURE_INSERT,
				K::PLATFORM_FEATURE_DEFAULT
			], false);

		if (($c == 0) && $hasDefaultValues)
		{
			$stream->space()->keyword('DEFAULT VALUES');
			$context->popResolverContext();
			return $stream;
		}

		foreach ($this->columnValues as $columnName => $value)
		{
			if (!$tableStructure->offsetExists($columnName))
				throw new StatementException($this,
					'Invalid column "' . $columnName . '"');

			$columns[] = $context->getStatementBuilder()
				->getPlatform()
				->quoteIdentifier($columnName);
			$column = $tableStructure->offsetGet($columnName);
			/**
			 *
			 * @var ColumnStructure $column
			 */
			if (!($value instanceof TokenizableExpressionInterface))
			{
				$dataType = K::DATATYPE_UNDEFINED;
				if ($column->hasColumnProperty(K::COLUMN_DATA_TYPE))
					$dataType = $column->getColumnProperty(
						K::COLUMN_DATA_TYPE);

				$value = new Literal($value, $dataType);
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
					$columns[] = $context->getStatementBuilder()
						->getPlatform()
						->quoteIdentifier($name);
					if ($hasDefaultKeyword)
						$values[] = new Keyword(K::KEYWORD_DEFAULT);
					else
					{
						$x = Evaluator::evaluate(
							$column->getColumnProperty(
								K::COLUMN_DEFAULT_VALUE));
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
