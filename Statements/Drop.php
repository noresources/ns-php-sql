<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\ArrayUtil;
use NoreSources\SQL\Constants as K;

class DropTableQuery extends Statement
{

	public function __construct(TableStructure $structure = null)
	{
		$this->structure = $structure;
	}

	public function buildExpression(StatementContext $context)
	{
		$structure = $this->structure;
		if (!($structure instanceof TableStructure))
		{
			$structure = $context->getPivot();
		}

		if (!($structure instanceof TableStructure))
		{
			throw new StatementException($this, 'Missing or invalid table structure');
		}

		/**
		 * @todo IF EXISTS (if available)
		 */
		return 'DROP TABLE ' . $context->getCanonicalName($this->structure);
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		
	}

	/**
	 * @var TableStructure
	 */
	private $structure;
}
