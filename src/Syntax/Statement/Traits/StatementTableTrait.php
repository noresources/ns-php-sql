<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Traits;

use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\TableReference;
use NoreSources\Type\TypeDescription;

/**
 * Generic method to set the table to which the statement applies.
 */
trait StatementTableTrait
{

	/**
	 *
	 * @param TableStructure|string $table
	 *        	Table structure path.
	 * @param string|null $alias
	 */
	public function table($table, $alias = null)
	{
		if ($table instanceof TableStructure)
			$table = $table->getIdentifier();

		if (!(\is_string($table) || $table instanceof Identifier))
			throw new \InvalidArgumentException(
				'Invalid type for $table argument. ' . Identifier::class .
				', ' . TableStructure::class .
				' or string expected. Got ' .
				TypeDescription::getName($table));

		$this->statementTable = new TableReference($table, $alias);
		return $this;
	}

	/**
	 *
	 * @return \NoreSources\SQL\Syntax\TableReference
	 */
	public function getTable()
	{
		return $this->statementTable;
	}

	/**
	 *
	 * @var TableReference
	 */
	protected $statementTable;
}
