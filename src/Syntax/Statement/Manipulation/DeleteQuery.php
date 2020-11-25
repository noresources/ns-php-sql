<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Manipulation;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;
use NoreSources\SQL\Syntax\Statement\Traits\StatementTableTrait;
use NoreSources\SQL\Syntax\Statement\Traits\WhereConstraintTrait;

/**
 * DELETE query
 */
class DeleteQuery implements TokenizableStatementInterface
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

	public function getStatementType()
	{
		return K::QUERY_DELETE;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$tableStructure = $context->findTable($this->getTable()->path);

		$context->pushResolverContext($tableStructure);

		$stream->keyword('delete')
			->space()
			->keyword('from')
			->space()
			->expression($this->getTable(), $context);

		if ($this->whereConstraints instanceof \ArrayObject &&
			$this->whereConstraints->count())
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
