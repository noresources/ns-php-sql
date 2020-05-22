<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Statement\Manipulation;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Statement\Traits\StatementTableTrait;
use NoreSources\SQL\Statement\Traits\WhereConstraintTrait;

/**
 * DELETE query
 */
class DeleteQuery extends Statement
{

	use WhereConstraintTrait;
	use StatementTableTrait;

	/**
	 *
	 * @param TableStructure|string $table
	 */
	public function __construct($table = null)
	{
		$this->initializeWhereConstraints();
		if ($table !== null)
			$this->table($table);
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		$tableStructure = $context->findTable($this->getTable()->path);

		$context->pushResolverContext($tableStructure);
		$context->setStatementType(K::QUERY_DELETE);

		$stream->keyword('delete')
			->space()
			->keyword('from')
			->space()
			->expression($this->getTable(), $context);

		if ($this->whereConstraints instanceof \ArrayObject && $this->whereConstraints->count())
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
