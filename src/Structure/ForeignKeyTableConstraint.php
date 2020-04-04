<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\Constants as K;

/**
 *
 * @see https://www.sqlite.org/syntax/foreign-key-clause.html
 */
class ForeignKeyTableConstraint extends TableConstraint implements \IteratorAggregate, \Countable
{

	const ACTION_SET_NULL = K::FOREIGN_KEY_ACTION_SET_NULL;

	const ACTION_SET_DEFAULT = K::FOREIGN_KEY_ACTION_SET_DEFAULT;

	const ACTION_CASCADE = K::FOREIGN_KEY_ACTION_CASCADE;

	const ACTION_RESTRICT = K::FOREIGN_KEY_ACTION_RESTRICT;

	/**
	 * ON DELETE action.
	 */
	public $onDelete;

	/**
	 * ON UPDATE action.
	 */
	public $onUpdate;

	public function __construct(TableStructure $foreignTable, $name = '')
	{
		parent::__construct($name);
		$this->onDelete = null;
		$this->onUpdate = null;
		$this->foreignTable = $foreignTable;
		$this->columns = new \ArrayObject();
	}

	/**
	 *
	 * @return Number of columns on which the foreign key is applied
	 */
	public function count()
	{
		return $this->columns->count();
	}

	/**
	 *
	 * @return \NoreSources\SQL\Structure\TableStructure
	 */
	public function getForeignTable()
	{
		return $this->foreignTable;
	}

	public function getIterator()
	{
		return $this->columns->getIterator();
	}

	public function addColumn($columnName, $foreignColumnName)
	{
		$this->columns->offsetSet($columnName, $foreignColumnName);
	}

	/**
	 *
	 * @var TableStructure
	 */
	private $foreignTable;

	/**
	 * Key-value pairs (column names => foreign table column name)
	 *
	 * @var \ArrayObject
	 */
	private $columns;
}

