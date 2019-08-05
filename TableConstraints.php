<?php

namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\SQL\Constants as K;

/**
 * @see https://www.sqlite.org/syntax/foreign-key-clause.html
 *
 */
class TableConstraint
{
	const UNIQUE = K::TABLE_CONSTRAINT_UNIQUE;
	const PRIMARY_KEY = K::TABLE_CONSTRAINT_PRIMARY_KEY;
	const FOREIGN_KEY = K::TABLE_CONSTRAINT_FOREIGN_KEY;

	/**
	 * @var string
	 */
	public $constraintName;

	/**
	 * @param string $name Constraint name
	 */
	public function __construct($name = null)
	{
		$this->constraintName = $name;
	}
}

class KeyTableConstraint extends TableConstraint implements \ArrayAccess, \IteratorAggregate
{

	/**
	 * @var integer One of Constants::TABLE_CONSTRAINT_UNIQUE or Constants::TABLE_CONSTRAINT_PRIMARY_KEY
	 */
	public $type;

	public $onConflict;

	/**
	 * @param integer $type One of Constants::KEY_CONSTRAINT_UNIQUE or Constants::KEY_CONSTRAINT_PRIMARY
	 * @param array $columns
	 * @param string $onConflict One of Constants::KEY_CONFLICT_*
	 */
	public function __construct($type = self::PRIMARY, $columns = array (), $onConflict = null)
	{
		$this->type = $type;
		$this->onConflict = $onConflict;
		$this->columns = new \ArrayObject($columns);
	}

	/**
	 * @property-read \ArrayObject $columns
	 * @param string $member
	 * @throws \InvalidArgumentException
	 * @return \ArrayObject
	 */
	public function __get($member)
	{
		if ($member == 'columns')
			return $this->columns;
		throw new \InvalidArgumentException($member);
	}

	public function getIterator()
	{
		return $this->columns->getIterator();
	}

	public function offsetExists($offset)
	{
		return $this->columns->offsetExists($offset);
	}

	public function offsetSet($offset, $value)
	{
		return $this->columns->offsetSet($offset, $value);
	}

	public function offsetGet($offset)
	{
		return $this->columns->offsetGet($offset);
	}

	public function offsetUnset($offset)
	{
		return $this->columns->offsetUnset($offset);
	}

	private $columns;
}

/**
 * @see https://www.sqlite.org/syntax/foreign-key-clause.html
 */
class ForeignKeyTableConstraint extends TableConstraint implements \IteratorAggregate, \Countable
{
	const ACTION_SET_NULL = K::FOREIGN_KEY_ACTION_SET_NULL;
	const ACTION_SET_DEFAULT = K::FOREIGN_KEY_ACTION_SET_DEFAULT;
	const ACTION_CASCADE = K::FOREIGN_KEY_ACTION_CASCADE;
	const ACTION_RESTRICT = K::FOREIGN_KEY_ACTION_RESTRICT;

	public $onDelete;

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
	 * @param string $member
	 * @property-read TableStructure $foreignTable
	 * @property-read \ArrayObject $columns
	 * @throws \InvalidArgumentException
	 * @return \NoreSources\SQL\TableStructure|ArrayObject
	 */
	public function __get($member)
	{
		if ($member == 'foreignTable')
			return $this->foreignTable;
		if ($member == 'columns')
			return $this->columns;

		throw new \InvalidArgumentException($member);
	}

	public function count ()
	{
		return $this->columns->count();
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
	 * @var TableStructure
	 */
	private $foreignTable;

	/**
	 * Key-value pairs (column names => foreign table column name)
	 * @var \ArrayObject
	 */
	private $columns;
}

