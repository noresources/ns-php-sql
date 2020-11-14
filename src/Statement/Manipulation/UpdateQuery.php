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

use NoreSources\Expression\ExpressionInterface;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\ColumnData;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Statement\StatementException;
use NoreSources\SQL\Statement\Traits\ColumnValueTrait;
use NoreSources\SQL\Statement\Traits\StatementTableTrait;
use NoreSources\SQL\Statement\Traits\WhereConstraintTrait;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\TableStructure;

/**
 * UPDATE query
 */
class UpdateQuery extends Statement implements \ArrayAccess
{

	use ColumnValueTrait;
	use StatementTableTrait;
	use WhereConstraintTrait;

	/**
	 *
	 * @param NamespaceStructure|string $table
	 */
	public function __construct($table = null, $alias = null)
	{
		$this->initializeWhereConstraints();
		$this->columnValues = new \ArrayObject();

		if ($table !== null)
			$this->table($table, $alias);
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		if ($this->columnValues->count() == 0)
			throw new StatementException($this, 'No column value');

		$tableStructure = $context->findTable($this->getTable()->path);
		$context->pushResolverContext($tableStructure);
		$context->setStatementType(K::QUERY_UPDATE);
		/**
		 *
		 * @var TableStructure $tableStructure
		 */

		$stream->keyword('update')
			->space()
			->identifier(
			$context->getPlatform()
				->quoteIdentifierPath($tableStructure));

		if ($this->columnValues->count())
			$stream->space()
				->keyword('set')
				->space();

		$index = 0;
		foreach ($this->columnValues as $columnName => $value)
		{
			if (!$tableStructure->offsetExists($columnName))
				throw new StatementException($this,
					'Invalid column "' . $columnName . '"');

			if ($index > 0)
				$stream->text(',')->space();

			$column = $tableStructure->offsetGet($columnName);
			/**
			 *
			 * @var ColumnStructure $column
			 */

			if (!($value instanceof ExpressionInterface))
			{
				$type = K::DATATYPE_UNDEFINED;
				$value = new ColumnData($value, $column);
			}

			$stream->identifier(
				$context->getPlatform()
					->quoteIdentifier($columnName))
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
}
