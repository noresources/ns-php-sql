<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\EventMap;
use NoreSources\SQL\Structure\Traits\ConstraintNameTrait;

/**
 *
 * @see https://www.sqlite.org/syntax/foreign-key-clause.html
 */
class ForeignKeyTableConstraint implements \IteratorAggregate,
	TableConstraintInterface
{

	use ConstraintNameTrait;

	public function getConstraintFlags()
	{
		return 0;
	}

	public function __construct($foreignTable, $name = '')
	{
		$this->setName($name);
		$this->foreignTable = StructureElementIdentifier::make(
			$foreignTable);
		$this->columns = new \ArrayObject();
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
	 * @return StructureElementIdentifier
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
	 * @var StructureElementIdentifier
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

