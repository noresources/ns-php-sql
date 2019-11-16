<?php

// NAmespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\SQL\Constants as K;

class RecordsetException extends \ErrorException
{

	/**
	 *
	 * @var Recordset
	 */
	public $recordset;

	/**
	 *
	 * @param Recordset $recordset
	 * @param string $message
	 * @param integer $code
	 */
	public function __construct(Recordset $recordset, $message, $code = null)
	{
		parent::__construct($message, $code);
		$this->recordset = $recordset;
	}
}

/**
 * Recordset query result
 */
abstract class Recordset implements \Iterator, StatementOutputData, QueryResult, ns\ArrayConversion,
	\JsonSerializable
{

	use StatementOutputDataTrait;

	/**
	 * Fetch record row to an associative array
	 *
	 * @var integer
	 */
	const FETCH_ASSOCIATIVE = K::RECORDSET_FETCH_ASSOCIATIVE;

	/**
	 * Fetch record row to a indexed array
	 *
	 * @var integer
	 */
	const FETCH_INDEXED = K::RECORDSET_FETCH_INDEXED;

	/**
	 * Fetch record row to an array with both indexed and associative key
	 *
	 * @var integer
	 */
	const FETCH_BOTH = K::RECORDSET_FETCH_BOTH;

	/**
	 * Convert row values to the most accurate PHP object
	 * according result column type
	 *
	 * @var integer
	 */
	const FETCH_UNSERIALIZE = K::RECORDSET_FETCH_UBSERIALIZE;

	/**
	 *
	 * @property-read string $rowIndex Current row index
	 * @property-read integer $flags Recordset flags
	 * @param string $member
	 * @throws \InvalidArgumentException
	 * @return number
	 */
	public function __get($member)
	{
		if ($member == 'rowIndex')
			return $this->rowIndex;

		if ($member == 'flags')
			return $this->flags;

		throw new \InvalidArgumentException(
			$member . ' is not a property of ' . \get_called_class());
	}

	/**
	 * Set recordset public flags
	 *
	 * @param integer $flags
	 */
	public function setFlags($flags)
	{
		$previousFlags = $this->flags;

		$this->flags &= ~self::PUBLIC_FLAGS;
		$flags &= self::PUBLIC_FLAGS;

		$this->rowIndex = -1;
		$this->flags |= $flags;

		if ($previousFlags != $this->flags)
			$this->updateRecord();
	}

	public function setResultColumns(ResultColumnMap $columns)
	{
		$this->resultColumns = $columns;
	}

	public function getArrayCopy()
	{
		$rows = [];
		foreach ($this as $index => $row)
		{
			/**
			 *
			 * @todo fix valid()
			 */
			if ($row === false)
				continue;
			$rows[$index] = $row;
		}
		return $rows;
	}

	public function jsonSerialize()
	{
		return $this->getArrayCopy();
	}

	/**
	 * Row index
	 *
	 * @return integer Current row index or -1 if iterator is not initialized or at end of recordset
	 */
	public function key()
	{
		if ($this->flags & self::POSITION_FLAGS)
			return -1;
		return $this->rowIndex;
	}

	/**
	 *
	 * @return \ArrayObject
	 */
	public function current()
	{
		if (($this->flags & self::POSITION_FLAGS) == 0)
		{
			return $this->record;
		}

		if (($this->flags & self::POSITION_BEGIN) == self::POSITION_BEGIN)
		{
			$this->next();
		}

		if ($this->valid())
		{
			return $this->record;
		}

		return false;
	}

	public function next()
	{
		$this->internalRecord = $this->fetch($this->rowIndex + 1);

		if ($this->internalRecord)
			$this->setIteratorPosition($this->rowIndex + 1, 0);
		else
			$this->setIteratorPosition(-1, self::POSITION_END);

		$this->updateRecord();
	}

	/**
	 *
	 * @return boolean
	 */
	public function valid()
	{
		return (($this->flags & self::POSITION_END) == 0);
	}

	public function rewind()
	{
		$r = $this->reset();
		$this->setIteratorPosition(-1, self::POSITION_BEGIN);

		if ($r === true)
		{
			$this->next();
		}
		elseif (\is_array($r))
		{
			$this->internalRecord = $r;
			$this->setIteratorPosition(0, 0);
			$this->updateRecord();
		}
		else
		{
			$this->setIteratorPosition(-1, self::POSITION_END);
			$this->updateRecord();
		}
	}

	protected function __construct($data = null)
	{
		$this->initializeStatementOutputData($data);

		$this->flags = self::FETCH_BOTH;
		$this->record = [];
		$this->internalRecord = false;
		$this->setIteratorPosition(-1, self::POSITION_BEGIN);
	}

	// Internal flags
	const PUBLIC_FLAGS = K::RECORDSET_PUBLIC_FLAGS;

	const POSITION_BEGIN = 0x10;

	const POSITION_END = 0x20;

	const POSITION_FLAGS = 0x30;

	/**
	 * Fetch a row from the DBMS recordset
	 *
	 * @param integer $index
	 *        	The expected row to fetch
	 * @return array|false Indexed array of column values or @c false if no mre column can be retreived
	 */
	abstract protected function fetch($index);

	/**
	 * Reset internal recordset
	 *
	 * @return array|boolean On success, return first row or @c true. On error, @c false
	 */
	abstract protected function reset();

	protected function setIteratorPosition($index, $positionFlag)
	{
		$this->rowIndex = $index;
		$this->flags &= ~self::POSITION_FLAGS;
		$positionFlag &= self::POSITION_FLAGS;
		$this->flags |= $positionFlag;
	}

	private function updateRecord()
	{
		$this->record = [];
		if ($this->internalRecord)
		{
			$fetchFlags = self::FETCH_BOTH | self::FETCH_UNSERIALIZE;

			if (($this->flags & $fetchFlags) == self::FETCH_INDEXED)
			{
				$this->record = $this->internalRecord;
				return;
			}

			foreach ($this->internalRecord as $index => $value)
			{
				if ($this->flags & self::FETCH_UNSERIALIZE)
				{
				/**
				 *
				 * @todo unserialize if needed
				 */
				}

				if ($this->flags & self::FETCH_INDEXED)
					$this->record[$index] = $value;
				if ($this->flags & self::FETCH_ASSOCIATIVE)
				{
					$column = $this->resultColumns->getColumn($index);
					$this->record[$column->name] = $value;
				}
			}
		}
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $record;

	/**
	 *
	 * @var array|NULL
	 */
	private $internalRecord;

	/**
	 *
	 * @var integer
	 */
	private $rowIndex;

	/**
	 *
	 * @var integer
	 */
	private $flags;

	/**
	 *
	 * @var ResultColumnMap
	 */
	private $resultColumns;
}
