<?php

// NAmespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\SQL\Constants as K;

abstract class Recordset implements \Iterator
{

	const FETCH_ASSOCIATIVE = K::RECORDSET_FETCH_ASSOCIATIVE;

	const FETCH_INDEXED = K::RECORDSET_FETCH_INDEXED;

	const FETCH_BOTH = K::RECORDSET_FETCH_BOTH;

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

		return FALSE;
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
	 * @var integer
	 */
	protected $rowIndex;

	/**
	 *
	 * @var \ArrayObject
	 */
	protected $record;
}
