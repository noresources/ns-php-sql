<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

// Namespace
namespace NoreSources\SQL\Statement;

// Aliases
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\Table;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Structure\TableStructure;

/**
 * DROP TABLE statement
 */
class DropTableQuery extends Statement
{

	public function __construct($table)
	{
		if ($table instanceof TableStructure)
		{
			$table = $table->getPath();
		}

		$this->table = new Table($table);
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		$builderFlags = $context->getStatementBuilder()->getBuilderFlags(K::BUILDER_DOMAIN_GENERIC);
		$builderFlags |= $context->getStatementBuilder()->getBuilderFlags(K::BUILDER_DOMAIN_DROP_TABLE);

		$tableStructure = $context->findTable($this->table->path);

		$context->pushResolverContext($tableStructure);

		$stream->keyword('drop')
			->space()
			->keyword('table');
		if ($builderFlags & K::BUILDER_IF_EXISTS)
		{
			$stream->space()
				->keyword('if')
				->space()
				->keyword('exists');
		}

		$stream->space()->expression($this->table, $context);
		$context->popResolverContext();
		return $stream;
	}

	/**
	 *
	 * @var NoreSources\Expression\Table
	 */
	private $table;
}
