<?php
namespace NoreSources\SQL;

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

