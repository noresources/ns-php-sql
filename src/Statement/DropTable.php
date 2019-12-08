<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\SQL\Constants as K;

class DropTableQuery extends Statement
{

	public function __construct($table)
	{
		if ($table instanceof TableStructure)
		{
			$table = $table->getPath();
		}

		$this->table = new TableReference($table);
	}

	public function tokenize(TokenStream &$stream, StatementContext $context)
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

		$stream->space()->identifier($context->getCanonicalName($tableStructure));
		$context->popResolverContext();
		return $stream;
	}

	/**
	 *
	 * @var TableReference
	 */
	private $table;
}
