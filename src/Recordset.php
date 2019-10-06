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

abstract class Recordset implements \Iterator
{

	const FETCH_ASSOCIATIVE = K::RECORDSET_FETCH_ASSOCIATIVE;

	const FETCH_INDEXED = K::RECORDSET_FETCH_INDEXED;

	const FETCH_BOTH = K::RECORDSET_FETCH_BOTH;

	/**
	 *
	 * @property-read string $rowIndex Current row index
	 * @param string $member
	 * @throws \InvalidArgumentException
	 * @return number
	 */
	public function __get($member)
	{
		if ($member == 'rowIndex')
			return $this->rowIndex;

		throw new \InvalidArgumentException(
			$member . ' is not a property of ' . \get_called_class());
	}

	abstract function getColumnCount();

	public function setFlags($flags)
	{
		$this->flags &= ~self::PUBLIC_FLAGS;
		$flags &= self::PUBLIC_FLAGS;

		$this->rowIndex = -1;
		$this->flags |= $flags;
	}

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
		$this->setPosition(-1, self::POSITION_BEGIN);
	}

	protected function __construct()
	{
		$this->flags = self::FETCH_BOTH;
		$this->record = new \ArrayObject();
		$this->setPosition(-1, self::POSITION_BEGIN);
	}

	// Internal flags
	const PUBLIC_FLAGS = 0x03;

	const POSITION_BEGIN = 0x10;

	const POSITION_END = 0x20;

	const POSITION_FLAGS = 0x30;

	protected function setPosition($index, $positionFlag)
	{
		$this->rowIndex = $index;
		$this->flags &= ~self::POSITION_FLAGS;
		$positionFlag &= self::POSITION_FLAGS;
		$this->flags |= $positionFlag;
	}

	/**
	 *
	 * @var integer
	 */
	protected $flags;

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
}
