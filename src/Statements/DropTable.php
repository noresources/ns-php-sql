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
		$tableStructure = $context->findTable($this->table->path);
		/**
		 * @todo IF EXISTS (if available)
		 */
		
		return $stream->keyword('drop')->space()->keyword('table')->space()->identifier($context->getCanonicalName($tableStructure));
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
