<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\SQL\DBMS\SQLite\SQLiteConstants as K;
use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Statement\ResultColumn;
use NoreSources\SQL\Statement\ResultColumnMap;
use NoreSources\SQL\Statement\StatementOutputDataInterface;

class SQLiteRecordset extends Recordset
{

	public function __construct(\SQLite3Result $result, $data = null)
	{
		parent::__construct($data);
		$this->result = $result;
		if (!($data instanceof StatementOutputDataInterface))
		{
			$map = $this->getResultColumns();
			for ($i = 0; $i < $result->numColumns(); $i++)
			{
				$column = null;
				if ($i < $map->count())
					$column = $map->getColumn($i);
				else
				{
					$column = new ResultColumn(K::DATATYPE_UNDEFINED);
					$column->name = $result->columnName($i);
				}

				if ($column->getDataType() == K::DATATYPE_UNDEFINED)
					$column->setColumnProperty(K::COLUMN_DATA_TYPE,
						SQLiteConnection::dataTypeFromSQLiteDataType(
							$result->columnType($i)));

				if ($i >= $map->count())
					$map->setColumn($i, $column);
			}
		}
	}

	public function __destruct()
	{
		$this->result->finalize();
	}

	public function setResultColumns(ResultColumnMap $columns)
	{
		parent::setResultColumns($columns);
		foreach ($columns as $index => $column)
			$columns->name = $this->result->columnName($index);
	}

	public function getColumnCount()
	{
		return $this->result->numColumns();
	}

	protected function fetch($index)
	{
		return $this->result->fetchArray(\SQLITE3_NUM);
	}

	public function reset()
	{
		return $this->result->reset();
	}

	/**
	 *
	 * @var \SQLite3Result
	 */
	private $result;
}