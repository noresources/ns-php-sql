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
use NoreSources\SQL\Expression\TableReference;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Statement\Traits\WhereConstraintTrait;
use NoreSources\SQL\Structure\TableStructure;

/**
 * DELETE query
 */
class DeleteQuery extends Statement
{

	use WhereConstraintTrait;

	/**
	 *
	 * @param TableStructure|string $table
	 */
	public function __construct($table)
	{
		$this->initializeWhereConstraints();

		if ($table instanceof TableStructure)
		{
			$table = $table->getPath();
		}

		$this->table = new TableReference($table);
	}

	/**
	 *
	 * @param Evaluable $args...
	 *        	Evaluable expression list
	 * @return \NoreSources\SQL\Statement\Manipulation\DeleteQuery
	 */
	public function where()
	{
		return $this->addConstraints($this->whereConstraints, func_get_args());
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		$tableStructure = $context->findTable($this->table->path);

		$context->pushResolverContext($tableStructure);
		$context->setStatementType(K::QUERY_DELETE);

		$stream->keyword('delete')
			->space()
			->keyword('from')
			->space()
			->expression($this->table, $context);

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

	/**
	 *
	 * @var TableReference
	 */
	private $table;
}
