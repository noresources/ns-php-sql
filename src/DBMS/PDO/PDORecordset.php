<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\PDO;

use NoreSources\Container\Container;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\MySQL\MySQLConstants as K;
use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Result\RecordsetException;
use NoreSources\SQL\Structure\ArrayColumnDescription;

class PDORecordset extends Recordset
{

	const PDO_SCROLLABLE = 0x1;

	/**
	 *
	 * @param \PDOStatement $pdo
	 */
	public function __construct(\PDOStatement $pdo,
		ConnectionInterface $connection, $data = null)
	{
		parent::__construct($connection, $data);
		$this->pdoStatement = $pdo;
		$this->cache = new \ArrayObject();
		$this->pdoFlags = 0;

		try
		{
			if (@$this->pdoStatement->getAttribute(\PDO::ATTR_CURSOR) ==
				\PDO::CURSOR_SCROLL)
			{
				$this->pdoFlags |= self::PDO_SCROLLABLE;
			}
		}
		catch (\PDOException $e)
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
					$column = $map->get($i);
				else
					$column = new ArrayColumnDescription();

				if ($meta)
				{
					if ($column->getDataType() == K::DATATYPE_UNDEFINED)
					{
						$pdoType = Container::keyValue($meta, 'pdo_type');
						$dataType = PDOConnection::getDataTypeFromPDOType(
							$pdoType);
						$column->setColumnProperty(K::COLUMN_DATA_TYPE,
							$dataType);
					}

					if (!$column->has(K::COLUMN_NAME))
						$column->setColumnProperty(K::COLUMN_NAME,
							Container::keyValue($meta, 'name',
								'column' . $i));

					if (($len = Container::keyValue($meta, 'len', -1)) >
						0)
						$column->setColumnProperty(K::COLUMN_LENGTH,
							$len);
					if (($precision = Container::keyValue($meta,
						'precision', -1)) > 0)
						$column->setColumnProperty(K::COLUMN_LENGTH,
							$precision);

					if (($typeName = Container::keyValue($meta,
						'driver:decl_type')))
						$column->setColumnProperty(K::COLUMN_TYPE_NAME,
							$typeName);
				}
				if ($i >= $map->count())
					$map->setColumn($i, $column);
			}
		}
		catch (\PDOException $e)
		{}

		if ($this->getResultColumns()->count() == 0)
			throw new \Exception('Unable to get column informations');
	}

	public function __destruct()
	{
		$this->pdoStatement->closeCursor();
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