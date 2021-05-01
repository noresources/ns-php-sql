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
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\ColumnData;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\StatementException;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;
use NoreSources\SQL\Syntax\Statement\Traits\ColumnValueTrait;
use NoreSources\SQL\Syntax\Statement\Traits\StatementTableTrait;
use NoreSources\SQL\Syntax\Statement\Traits\WhereConstraintTrait;

/**
 * UPDATE query
 */
class UpdateQuery implements TokenizableStatementInterface, \ArrayAccess
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

	public function getStatementType()
	{
		return K::QUERY_UPDATE;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		if ($this->columnValues->count() == 0)
			throw new StatementException($this, 'No column value');

		$tableStructure = $context->findTable(
			\strval($this->getTable()));
		$context->pushResolverContext($tableStructure);
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
			if (!$tableStructure->getColumns()->has($columnName))
				throw new StatementException($this,
					'Invalid column "' . $columnName . '"');

			if ($index > 0)
				$stream->text(',')->space();

			$column = $tableStructure->getColumns()->get($columnName);
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
