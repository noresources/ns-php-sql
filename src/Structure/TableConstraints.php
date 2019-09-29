<?php
namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\SQL\Constants as K;

/**
 *
 * @see https://www.sqlite.org/syntax/foreign-key-clause.html
 *
 */
class TableConstraint
{

	/**
	 *
	 * @var string
	 */
	public $constraintName;

	/**
	 *
	 * @param string $name
	 *        	Constraint name
	 */
	public function __construct($name = null)
	{
		$this->constraintName = $name;
	}
}

class ColumnTableConstraint extends TableConstraint implements \ArrayAccess, \IteratorAggregate,
	\Countable
{

	/**
	 *
	 * @param array $columns
	 *        	Column names on which the key applies.
	 * @param unknown $name
	 *        	Constraint name
	 */
	protected function __construct($columns = [], $name = null)
	{
	parent::__construct($name);
	$this->columns = new \ArrayObject($columns);
}

	/**
	 *
	 * @property-read \ArrayObject $columns Column names on which the key applies.
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

	public function count()
	{
		return $this->columns->count();
	}

	/**
	 * Get an interator on columns
	 *
	 * {@inheritdoc}
	 * @see IteratorAggregate::getIterator()
	 */
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

	/**
	 * Column names on which the key applies.
	 *
	 * @var \ArrayObject
	 */
	private $columns;
}

class PrimaryKeyTableConstraint extends ColumnTableConstraint
{

	/*
	 * @param array $columns Column names on which the key applies.
	 * @param unknown $name Constraint name
	 */
	public function __construct($columns = [], $name = null)
	{
		parent::__construct($columns, $name);
	}
}

class UniqueTableConstraint extends ColumnTableConstraint
{

	/*
	 * @param array $columns Column names on which the key applies.
	 * @param unknown $name Constraint name
	 */
	public function __construct($columns = [], $name = null)
	{
		parent::__construct($columns, $name);
	}
}

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

	public function count()
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

