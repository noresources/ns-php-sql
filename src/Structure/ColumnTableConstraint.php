<?php
namespace NoreSources\SQL;

class ColumnTableConstraint extends TableConstraint implements \ArrayAccess, \IteratorAggregate,
	\Countable
{

	/**
	 *
	 * @param array $columns
	 *        	Column names on which the key applies.
	 * @param string $name
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

