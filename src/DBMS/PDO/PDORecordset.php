<?php
/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\PDO;

// Aliases
use NoreSources\SQL\QueryResult\Recordset;

class PDORecordset extends Recordset
{

	const PDO_SCROLLABLE = 0x1;

	public function __construct(PDOPreparedStatement $statement)
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

	public function getColumnCount()
	{
		return $this->statement->getPDOStatement()->columnCount();
	}

	protected function fetch($index)
	{
		if ($this->cache->offsetExists($index))
		{
			return $this->cache->offsetGet($index);
		}

		$pdo = $this->statement->getPDOStatement();
		$data = $pdo->fetch(\PDO::FETCH_NUM, \PDO::FETCH_ORI_NEXT);

		if ($data === false)
			return false;

		if ($index >= 0)
			$this->cache->offsetSet($index, $data);

		return $data;
	}

	public function reset()
	{
		if (($this->flags & self::POSITION_FLAGS) == self::POSITION_BEGIN)
			return true;

		$pdo = $this->statement->getPDOStatement();
		$data = $pdo->fetch(\PDO::FETCH_NUM, \PDO::FETCH_ORI_FIRST);

		if ($data === false)
			return false;

		$this->cache->offsetSet(0, $data);
		return $data;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Iterator::next()
	 */
	public function _next()
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
	 * @var PDOPreparedStatement
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