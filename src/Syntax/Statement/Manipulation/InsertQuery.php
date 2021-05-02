<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Manipulation;

use NoreSources\Expression\ExpressionInterface;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\ColumnData;
use NoreSources\SQL\Syntax\Evaluator;
use NoreSources\SQL\Syntax\Keyword;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\StatementException;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;
use NoreSources\SQL\Syntax\Statement\Traits\ColumnValueTrait;
use NoreSources\SQL\Syntax\Statement\Traits\StatementTableTrait;

/**
 * INSERT query
 */
class InsertQuery implements TokenizableStatementInterface, \ArrayAccess
{

	use ColumnValueTrait;
	use StatementTableTrait;

	/**
	 * Alias of table() method
	 *
	 * @param TableStructure|string $table
	 *        	Table structure path.
	 * @return \NoreSources\SQL\Syntax\Statement\Manipulation\InsertQuery
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

	public function getStatementType()
	{
		return K::QUERY_INSERT;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();

		$tableStructure = $context->findTable(
			\strval($this->getTable()));

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
			$context->getPlatform()
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
				K::FEATURE_INSERT,
				K::FEATURE_DEFAULTVALUES
			], false);

		$hasDefaultKeyword = $platform->queryFeature(
			[
				K::FEATURE_INSERT,
				K::FEATURE_DEFAULT
			], false);

		if (($c == 0) && $hasDefaultValues)
		{
			$stream->space()->keyword('DEFAULT VALUES');
			$context->popResolverContext();
			return $stream;
		}

		foreach ($this->columnValues as $columnName => $value)
		{
			if (!$tableStructure->getColumns()->has($columnName))
				throw new StatementException($this,
					'Invalid column "' . $columnName . '"');

			$columns[] = $context->getPlatform()->quoteIdentifier(
				$columnName);
			$column = $tableStructure->getColumns()->get($columnName);
			/**
			 *
			 * @var ColumnStructure $column
			 */
			if (!($value instanceof ExpressionInterface))
			{
				$dataType = K::DATATYPE_UNDEFINED;
				$value = new ColumnData($value, $column);
			}

			$values[] = $value;
		}

		if ($c == 0)
		{
			foreach ($tableStructure->getColumns() as $name => $column)
			{
				/**
				 *
				 * @var ColumnStructure $column
				 */

				if ($column->has(K::COLUMN_DEFAULT_VALUE))
				{
					$c++;
					$columns[] = $context->getPlatform()->quoteIdentifier(
						$name);
					if ($hasDefaultKeyword)
						$values[] = new Keyword(K::KEYWORD_DEFAULT);
					else
					{
						$x = Evaluator::evaluate(
							$column->get(K::COLUMN_DEFAULT_VALUE));
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
