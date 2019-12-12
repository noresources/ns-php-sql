<?php

// Namespace
namespace NoreSources\SQL\Statement;

// Aliases
use NoreSources\SQL\BuildContext;
use NoreSources\SQL\TableStructure;
use NoreSources\SQL\TokenStream;
use NoreSources\SQL\Expression\Table;

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

	public function tokenize(TokenStream &$stream, BuildContext $context)
	{
		$builderFlags = $context->getBuilderFlags(K::BUILDER_DOMAIN_GENERIC);
		$builderFlags |= $context->getBuilderFlags(K::BUILDER_DOMAIN_DROP_TABLE);

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
