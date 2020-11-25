<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\PostgreSQL;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Result\Traits\SeekableRecordsetTrait;
use NoreSources\SQL\Structure\ArrayColumnDescription;

class PostgreSQLRecordset extends Recordset implements
	\SeekableIterator, \Countable
{

	use SeekableRecordsetTrait;

	/**
	 *
	 * @param resource $resource
	 * @param PostgreSQLPreparedStatement|null $data
	 */
	public function __construct($resource, $data = null)
	{
		parent::__construct($data);
		$this->resource = $resource;
		$map = $this->getResultColumns();
		for ($i = 0; $i < \pg_num_fields($this->resource); $i++)
		{
			$column = null;
			if ($i < $map->count())
				$column = $map->getColumn($i);
			else
				$column = new ArrayColumnDescription();

			$column->setColumnProperty(K::COLUMN_NAME,
				\pg_field_name($this->resource, $i));

			if ($column->getDataType() == K::DATATYPE_UNDEFINED)
			{
				$typename = \pg_field_type($this->resource, $i);
				$column->setColumnProperty(K::COLUMN_DATA_TYPE,
					PostgreSQLTypeRegistry::getInstance()->get(
						$typename)
						->get(K::TYPE_DATA_TYPE));
			}

			if ($i >= $map->count())
				$map->setColumn($i, $column);
		}
	}

	public function __destruct()
	{
		\pg_free_result($this->resource);
	}

	public function getColumnCount()
	{
		return \pg_num_fields($this->resource);
	}

	public function count()
	{
		return \pg_num_rows($this->resource);
	}

	public function reset()
	{
		return @\pg_result_seek($this->resource, 0);
	}

	protected function fetch($index)
	{
		return @\pg_fetch_array($this->resource, $index, PGSQL_NUM);
	}

	/**
	 *
	 * @param integer $position
	 * @return boolean
	 */
	protected function seekRecord($position)
	{
		return @\pg_result_seek($this->resource, $position);
	}

	/**
	 *
	 * @var resource PostgreSQL result resource
	 */
	private $resource;
}