<?php

// NAmespace
namespace NoreSources\SQL;

// Aliases
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
abstract class Recordset implements \Iterator, StatementOutputData, QueryResult
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
		$this->flags &= ~self::PUBLIC_FLAGS;
		$flags &= self::PUBLIC_FLAGS;

		$this->rowIndex = -1;
		$this->flags |= $flags;
	}

	public function setResultColumns(ResultColumnMap $columns)
	{
		$this->resultColumns = $columns;
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
		$this->setIteratorPosition(-1, self::POSITION_BEGIN);
	}

	protected function __construct()
	{
		$this->resultColumns = new ResultColumnMap();
		$this->statementType = K::QUERY_SELECT;

		$this->flags = self::FETCH_BOTH;
		$this->record = new \ArrayObject();
		$this->setIteratorPosition(-1, self::POSITION_BEGIN);
	}

	// Internal flags
	const PUBLIC_FLAGS = K::RECORDSET_PUBLIC_FLAGS;

	const POSITION_BEGIN = 0x10;

	const POSITION_END = 0x20;

	const POSITION_FLAGS = 0x30;

	protected function setIteratorPosition($index, $positionFlag)
	{
		$this->rowIndex = $index;
		$this->flags &= ~self::POSITION_FLAGS;
		$positionFlag &= self::POSITION_FLAGS;
		$this->flags |= $positionFlag;
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	protected $record;

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
