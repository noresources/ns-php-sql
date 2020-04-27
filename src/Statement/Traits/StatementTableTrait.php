<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

//
namespace NoreSources\SQL\Statement\Traits;

use NoreSources\TypeDescription;
use NoreSources\SQL\Expression\TableReference;
use NoreSources\SQL\Structure\TableStructure;

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
			$table = $table->getPath();

		if (!\is_string($table))
			throw new \InvalidArgumentException(
				'Invalid type for $table argument. ' . TableStructure::class .
				' or string expected. Got ' . TypeDescription::getName($table));

		$this->statementTable = new TableReference($table, $alias);
		return $this;
	}

	/**
	 *
	 * @return \NoreSources\SQL\Expression\TableReference
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
