<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\EventMap;
use NoreSources\SQL\Structure\Traits\StructureElementTrait;

/**
 *
 * @see https://www.sqlite.org/syntax/foreign-key-clause.html
 */
class ForeignKeyTableConstraint implements \IteratorAggregate,
	TableConstraintInterface
{

	use StructureElementTrait;

	public function getConstraintFlags()
	{
		return K::CONSTRAINT_COLUMN_FOREIGN_KEY;
	}

	public function __construct($foreignTable, $columnMapping = array(),
		$name = null)
	{
		$this->initializeStructureElement($name);
		$this->foreignTable = Identifier::make($foreignTable);
		$this->columns = new \ArrayObject();
		foreach ($columnMapping as $c => $ref)
			$this->addColumn($c, $ref);
	}

	/**
	 *
	 * @return EventMap
	 */
	public function getEvents()
	{
		if (!isset($this->events))
			$this->events = new EventMap();
		return $this->events;
	}

	/**
	 *
	 * @return Identifier
	 */
	public function getForeignTable()
	{
		return $this->foreignTable;
	}

	public function getIterator()
	{
		return $this->columns->getIterator();
	}

	public function getColumns()
	{
		return $this->columns;
	}

	public function addColumn($columnName, $foreignColumnName)
	{
		$this->columns->offsetSet($columnName, $foreignColumnName);
	}

	public function __clone()
	{
		$this->cloneStructureElement();
		if (isset($this->foreignTable))
			$this->foreignTable = clone $this->foreignTable;
		if (isset($this->columns))
			$this->columns = clone $this->columns;
		if (isset($this->events))
			$this->events = clone $this->events;
	}

	/**
	 *
	 * @var Identifier
	 */
	private $foreignTable;

	/**
	 * Key-value pairs (column names => foreign table column name)
	 *
	 * @var \ArrayObject
	 */
	private $columns;

	/**
	 *
	 * @var EventMap
	 */
	private $events;
}

