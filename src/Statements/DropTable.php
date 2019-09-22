<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\ArrayUtil;
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

		return $stream->space()
			->identifier($context->getCanonicalName($tableStructure));
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($this, $context, $flags);
	}

	/**
	 * @var TableReference
	 */
	private $table;
}
