<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\PDO;

use NoreSources\Container;
use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Result\RecordsetException;
use NoreSources\SQL\Syntax\Statement\ResultColumn;

class PDORecordset extends Recordset
{

	const PDO_SCROLLABLE = 0x1;

	/**
	 *
	 * @param \PDOStatement $pdo
	 */
	public function __construct(\PDOStatement $pdo, $data = null)
	{
		parent::__construct($data);
		$this->pdoStatement = $pdo;
		$this->cache = new \ArrayObject();
		$this->pdoFlags = 0;

		try
		{
			if ($this->pdoStatement->getAttribute(\PDO::ATTR_CURSOR) ==
				\PDO::CURSOR_SCROLL)
			{
				$this->pdoFlags |= self::PDO_SCROLLABLE;
			}
		}
		catch (\Exception $e)
		{
			$this->pdoFlags &= ~self::PDO_SCROLLABLE;
		}

		try
		{
			$map = $this->getResultColumns();
			for ($i = 0; $i < $pdo->columnCount(); $i++)
			{
				$meta = $pdo->getColumnMeta($i);

				if ($i < $map->count())
					$column = $map->getColumn($i);
				else
				{
					$pdoType = Container::keyValue($meta, 'pdo_type');
					$dataType = PDOConnection::getDataTypeFromPDOType(
						$pdoType);
					$column = new ResultColumn($dataType);
					$column->name = Container::keyValue($meta, 'name',
						'column' . $i);
				}
			}
		}
		catch (\Exception $e)
		{}
	}

	public function __destruct()
	{
		$this->pdoStatement->closeCursor();
	}

	public function getColumnCount()
	{
		return $this->pdoStatement->columnCount();
	}

	protected function fetch($index)
	{
		$data = $this->pdoStatement->fetch(\PDO::FETCH_NUM,
			\PDO::FETCH_ORI_NEXT);

		if ($data === false)
			return false;

		return $data;
	}

	protected function reset()
	{
		if (($this->flags & self::POSITION_FLAGS) == self::POSITION_BEGIN)
			return true;

		if (($this->pdoFlags & self::PDO_SCROLLABLE) ==
			self::PDO_SCROLLABLE)
		{
			return $this->pdoStatement->fetch(\PDO::FETCH_NUM,
				\PDO::FETCH_ORI_FIRST);
		}

		$result = $this->pdoStatement->closeCursor();
		if ($result === false)
			throw new RecordsetException($this, 'Unable to close cursor');

		$result = $this->pdoStatement->execute();
		if ($result === false)
			throw new RecordsetException($this,
				'Unable to re-execute PDO statement');

		return $result;
	}

	/**
	 *
	 * @var \PDOStatement
	 */
	private $pdoStatement;

	/**
	 *
	 * @var integer
	 */
	private $pdoFlags;
}