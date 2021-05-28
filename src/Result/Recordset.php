<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Result;

use NoreSources\ArrayRepresentation;
use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\DataUnserializerInterface;
use NoreSources\SQL\DBMS\DefaultDataUnserializer;
use NoreSources\SQL\Syntax\Statement\ResultColumnMap;
use NoreSources\SQL\Syntax\Statement\ResultColumnProviderInterface;
use NoreSources\SQL\Syntax\Statement\Traits\ResultColumnProviderTrait;

/**
 * Recordset query result
 */
abstract class Recordset implements \Iterator, StatementResultInterface,
	ArrayRepresentation, \JsonSerializable,
	ResultColumnProviderInterface, DataUnserializerInterface
{

	use ResultColumnProviderTrait;

	/**
	 * Retrieve the given column of the current record of the recordset
	 *
	 * @param Recordset $recordset
	 * @param integer|string $column
	 *        	Column index or name
	 * @return mixed Column value
	 */
	public static function columnValue(Recordset $recordset, $column = 0)
	{
		$previousFlags = $recordset->getFlags();
		$flags = self::FETCH_UNSERIALIZE;
		if (\is_integer($column))
			$flags |= self::FETCH_INDEXED;
		else
			$flags = self::FETCH_ASSOCIATIVE;

		$recordset->setFlags($flags);
		$value = Container::keyValue($recordset->current(), $column);
		$recordset->setFlags($previousFlags);
		return $value;
	}

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
	 * Set the default Recordset flags for all future Recordset instances
	 *
	 * @param integer $flags
	 */
	public static function setDefaultFlags($flags)
	{
		self::$defaultFlags = (self::$defaultFlags & ~self::PUBLIC_FLAGS) |
			($flags & self::PUBLIC_FLAGS);
	}

	/**
	 *
	 * @return integer Default flags set for each new Recordset instance
	 */
	public static function getDefaultFlags()
	{
		return self::$defaultFlags;
	}

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

		$this->flags = ($this->flags & ~self::PUBLIC_FLAGS) |
			($flags & self::PUBLIC_FLAGS);

		if (($this->flags & self::FETCH_BOTH) == 0)
			throw new RecordsetException($this,
				'One of FETCH_ASSOCIATIVE or FETCH_INDEXED flags must be set');

		if ($previousFlags != $this->flags)
			$this->updateRecord();
	}

	public function getFlags()
	{
		return $this->flags;
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
			$this->next();
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

	public function setDataUnserializer(
		DataUnserializerInterface $unserializer)
	{
		$this->unserializer = $unserializer;
	}

	public function unserializeColumnData($columnDescription, $data)
	{
		$unserializer = $this->unserializer;
		if (!($unserializer instanceof DataUnserializerInterface))
			$unserializer = DefaultDataUnserializer::getInstance();

		return $unserializer->unserializeColumnData($columnDescription,
			$data);
	}

	protected function __construct($data = null)
	{
		$this->initializeResultColumnData($data);

		$this->flags = self::getDefaultFlags();
		$this->record = [];
		$this->internalRecord = false;
		$this->setIteratorPosition(-1, self::POSITION_BEGIN);
		$this->unserializer = null;
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
	 *        	The expected row to fetch. This value is always set to
	 *        	"current row index + 1". It is mandatory for some DBMS implementations
	 * @return array|false Indexed array of column values or @c false if no mre column can be
	 *         retreived
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
				if (!$this->resultColumns->has($index))
				{
					if ($this->flags & self::FETCH_INDEXED)
						$this->record[$index] = $value;
					continue;
				}

				$column = $this->resultColumns->get($index);

				if ($this->flags & self::FETCH_UNSERIALIZE)
				{
					$u = $this;
					if ($column->has(K::COLUMN_UNSERIALIZER))
						$u = $column->get(K::COLUMN_UNSERIALIZER);

					$value = $u->unserializeColumnData($column, $value);
				}

				if ($this->flags & self::FETCH_INDEXED)
					$this->record[$index] = $value;
				if ($this->flags & self::FETCH_ASSOCIATIVE)
					$this->record[$column->getName()] = $value;
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

	/**
	 *
	 * @var DataUnserializerInterface
	 */
	private $unserializer;

	/**
	 *
	 * @var integer
	 */
	private static $defaultFlags;
}

Recordset::setDefaultFlags(Recordset::FETCH_BOTH);