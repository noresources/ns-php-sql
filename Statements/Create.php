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

		if (!($structure instanceof TableStructure && ($structure->count() > 0)))
		{
			throw new StatementException($this, 'Missing or invalid table structure');
		}

		/**
		 * @todo IF NOT EXISTS (if available)
		 */

		$s = 'CREATE TABLE ' . $context->getCanonicalName($this->structure);

		$instructions = array ();

		$s .= PHP_EOL . '(' . PHP_EOL;
		
		// Columns
		
		foreach ($this->structure as $name => $column)
		{
			$instructions[] = $context->getColumnDescription($column, $context);
		}

		// Constraints
		
		foreach ($structure->constraints as $constraint)
		{
			$instructions[] = $context->getTableConstraintDescription($structure, $constraint);
		}

		$s .= implode(',' . PHP_EOL, $instructions);

		$s .= PHP_EOL . ')';

		return $s;
	}

	/**
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Expression::traverse()
	 */
	public function traverse($callable, StatementContext $context, $flags = 0)
	{}

	/**
	 * @var TableStructure
	 */
	private $structure;
}
