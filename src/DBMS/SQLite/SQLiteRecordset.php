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
use NoreSources\SQL\Structure\ArrayColumnDescription;

class SQLiteRecordset extends Recordset
{

	public function __construct(\SQLite3Result $result, $data = null)
	{
		parent::__construct($data);
		$this->result = $result;
		$map = $this->getResultColumns();
		for ($i = 0; $i < $result->numColumns(); $i++)
		{
			$column = null;
			if ($i < $map->count())
				$column = $map->get($i);
			else
				$column = new ArrayColumnDescription();

			if (!$column->has(K::COLUMN_NAME))
				$column->setColumnProperty(K::COLUMN_NAME,
					$result->columnName($i));

			if (!$column->has(K::COLUMN_DATA_TYPE))
				$column->setColumnProperty(K::COLUMN_DATA_TYPE,
					SQLiteConnection::dataTypeFromSQLiteDataType(
						$result->columnType($i)));

			if ($i >= $map->count())
				$map->setColumn($i, $column);
		}
	}

	public function __destruct()
	{
		$this->result->finalize();
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