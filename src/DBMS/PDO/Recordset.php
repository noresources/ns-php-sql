<?php

// NAmespace
namespace NoreSources\SQL\PDO;

// Aliases
use NoreSources\SQL as sql;
use NoreSources as ns;
use NoreSources\SQL\Constants as K;

class Recordset extends sql\Recordset
{

	const PDO_SCROLLABLE = 0x1;

	public function __construct(PreparedStatement $statement)
	{
		parent::__construct($statement);
		$this->statement = $statement;
		$this->statement->acquirePDOStatement($this);
		$this->cache = new \ArrayObject();
		$this->pdoFlags = 0;
		$pdo = $statement->getPDOStatement();

		try
		{
			if ($pdo->getAttribute(\PDO::ATTR_CURSOR) == \PDO::CURSOR_SCROLL)
			{
				$this->pdoFlags |= self::PDO_SCROLLABLE;
			}
		}
		catch (\Exception $e)
		{
			$this->pdoFlags &= ~self::PDO_SCROLLABLE;
		}
	}

	public function __destruct()
	{
		$this->statement->releasePDOStatement($this);
	}

	/**
	 *
	 * @see https://www.php.net/manual/en/pdostatement.getcolumnmeta.php
	 */
	public function setResultColumns(sql\ResultColumnMap $columns)
	{
		parent::setResultColumns($columns);
		try
		{
			foreach ($columns as $index => &$column)
			{
				$meta = $this->statement->getPDOStatement()->getColumnMeta($index);
				$columns->name = $meta['name'];
				if ($columns->type == K::DATATYPE_UNDEFINED)
				{
					$columns->type = Connection::getDataTypeFromPDOType(
						ns\Container::keyValue($meta, 'pdo_type', \PDO::PARAM_STR));
				}
			}
		}
		catch (\ErrorException $e)
		{}
	}

	public function getColumnCount()
	{
		return $this->statement->getPDOStatement()->columnCount();
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Iterator::next()
	 */
	public function next()
	{
		$pdo = $this->statement->getPDOStatement();

		$fetchStyle = \PDO::FETCH_BOTH;
		if (($this->flags & self::FETCH_BOTH) == self::FETCH_BOTH)
			$fetchStyle = \PDO::FETCH_BOTH;
		elseif (($this->flags & self::FETCH_ASSOCIATIVE) == self::FETCH_ASSOCIATIVE)
			$fetchStyle = \PDO::FETCH_ASSOC;
		elseif (($this->flags | self::FETCH_INDEXED) == self::FETCH_INDEXED)
			$fetchStyle = \PDO::FETCH_NUM;

		$orientation = \PDO::FETCH_ORI_NEXT;
		$newIndex = $this->rowIndex + 1;

		if ((($this->flags & self::POSITION_BEGIN) == self::POSITION_BEGIN) && ($this->rowIndex == 0))
		{
			// Cursor was reset, we are already on the row 0
			$orientation = \PDO::FETCH_ORI_FIRST;
			$newIndex = 0;
		}

		if ($this->cache->offsetExists($newIndex))
		{
			$this->record->exchangeArray($this->cache->offsetGet($newIndex));
			$this->setIteratorPosition($newIndex, 0);
			return;
		}

		$a = $pdo->fetch($fetchStyle, $orientation);
		if ($a === FALSE)
		{
			$this->setIteratorPosition(-1, self::POSITION_END);
		}
		else
		{
			if (($this->pdoFlags & self::PDO_SCROLLABLE) == 0)
				$this->cache[$newIndex] = $a;
			$this->record->exchangeArray($a);
			$this->setIteratorPosition($newIndex, 0);
		}
	}

	public function rewind()
	{
		if ($this->flags & self::POSITION_BEGIN)
			return;

		if ($this->pdoFlags & self::PDO_SCROLLABLE)
			$this->setIteratorPosition(0, self::POSITION_BEGIN);
		else
			$this->setIteratorPosition(-1, self::POSITION_BEGIN);
	}

	/**
	 *
	 * @var PreparedStatement
	 */
	private $statement;

	/**
	 *
	 * @var integer
	 */
	private $pdoFlags;

	/**
	 *
	 * @var \ArrayObject
	 */
	private $cache;
}