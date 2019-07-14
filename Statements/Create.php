<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\ArrayUtil;
use NoreSources\SQL\Constants as K;

class CreateTableQuery extends Statement
{

	public function __construct(TableStructure $structure = null)
	{
		$this->structure = $structure;
	}

	/**
	 * 
	 * @property-read \NoreSources\SQL\TableStructure
	 * @param mixed $member
	 * @return \NoreSources\SQL\TableStructure|unknown
	 */
	public function __get($member)
	{
		if ($member == 'structure')
			return $this->structure;
		
		return $this->structure->$member;
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
		 * @todo IF NOT EXISTS (if available)
		 */

		$s = 'CREATE TABLE ' . $context->getCanonicalName($this->structure);

		if ($this->structure->count())
		{
			$s .= PHP_EOL . '(' . PHP_EOL;
			$first = true;
			foreach ($this->structure as $name => $column)
			{
				if ($first)
					$first = false;
				else
					$s .= ', ' . PHP_EOL;

				$s .= $context->getColumnDescription($column);
			}
			
			/**
			 * @todo Foreign keys etc.
			 */
			
			$s .= PHP_EOL . ')';
		}

		return $s;
	}

	/**
	 * 
	 * {@inheritDoc}
	 * @see \NoreSources\SQL\Expression::traverse()
	 */
	public function traverse($callable, StatementContext $context, $flags = 0)
	{}

	/**
	 * @var TableStructure
	 */
	private $structure;
}
