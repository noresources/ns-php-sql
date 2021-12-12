<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Manipulation;

use NoreSources\Container\Container;
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
use NoreSources\SQL\Syntax\Statement\Query\SelectQuery;
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
	 * Defines affected column(s)
	 *
	 * THis method should be used for INSERT INTO SELECT queries
	 *
	 * @return $this
	 */
	public function columns(/* ... */)
	{
		$args = func_get_args();
		foreach ($args as $name)
		{
			$this->setColumnData($name,
				Container::keyValue($this->columnValues, $name, null),
				false);
		}
		return $this;
	}

	/**
	 *
	 * @param SelectQuery $q
	 *        	SELECT query used as data source
	 * @return $this
	 */
	public function select(SelectQuery $q)
	{
		$this->selectQuery = $q;
		return $this;
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

		$insertSupportFlags = $platform->queryFeature(
			[
				K::FEATURE_INSERT,
				K::FEATURE_INSERT_FLAGS
			], 0);

		if (isset($this->selectQuery) &&
			(($insertSupportFlags & K::FEATURE_INSERT_FLAG_SELECT) == 0))
		{
			throw new StatementException($this,
				'INSERT INTO SELECT is not supported');
		}

		$hasDefaultValues = (($insertSupportFlags &
			K::FEATURE_INSERT_FLAG_DEFAULTVALUES) ==
			K::FEATURE_INSERT_FLAG_DEFAULTVALUES);

		$hasDefaultKeyword = (($insertSupportFlags &
			K::FEATURE_INSERT_FLAG_DEFAULT) ==
			K::FEATURE_INSERT_FLAG_DEFAULT);

		if (($c == 0) && $hasDefaultValues)
		{
			$stream->space()->keyword('DEFAULT VALUES');
			$context->popResolverContext();
			return $stream;
		}

		foreach ($this->columnValues as $columnName => $value)
		{
			$column = $context->findColumn($columnName);
			$columns[] = $context->getPlatform()->quoteIdentifier(
				$columnName);
			if (!isset($this->selectQuery))
			{

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
		}

		if (!isset($this->selectQuery))
		{
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
		}

		if ($c)
		{
			$stream->space()->text('(');
			$c = 0;
			foreach ($columns as $column)
			{
				if ($c)
					$stream->text(',')->space();
				$stream->identifier($column);
				$c++;
			}
			$stream->text(')');
		}

		if (isset($this->selectQuery))
		{
			$context->pushResolverContext($context->getPivot());
			$stream->space()->expression($this->selectQuery, $context);
			$context->popResolverContext();
		}
		else
		{
			$stream->space()
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
		}

		$context->popResolverContext();
		return $stream;
	}

	/**
	 *
	 * @var SelectQuery
	 */
	private $selectQuery;
}
