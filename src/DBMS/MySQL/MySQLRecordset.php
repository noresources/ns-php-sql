<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\Container;
use NoreSources\SQL\DBMS\MySQL\MySQLConstants as K;
use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Result\SeekableRecordsetTrait;
use NoreSources\SQL\Statement\ResultColumn;

/**
 *
 * @todo Seekable
 *
 */
class MySQLRecordset extends Recordset implements \Countable, \SeekableIterator
{

	use SeekableRecordsetTrait;

	public function __construct(\mysqli_result $result, $data = null)
	{
		parent::__construct($data);
		$this->mysqlResult = $result;

		/**
		 *
		 * @todo porcess fields
		 * @see https://www.php.net/manual/en/mysqli-result.fetch-fields.php
		 * @var array $fields
		 */
		$fields = $result->fetch_fields();
		$map = $this->getResultColumns();
		for ($i = 0; $i < $result->field_count; $i++)
		{
			$field = $fields[$i];
			$column = null;
			$created = false;
			if ($i < $map->count())
				$column = $map->getColumn($i);
			else
			{
				$created = true;
				$column = new ResultColumn(K::DATATYPE_UNDEFINED);
			}

			if (\strlen($column->name) == 0)
				$column->name = Container::keyValue($field, 'name',
					Container::keyValue($field, 'orgname'));

			if (!$column->hasColumnProperty(K::COLUMN_FRACTION_SCALE) &&
				Container::keyExists($field, 'decimals'))
				$column->setColumnProperty(K::COLUMN_FRACTION_SCALE,
					intval($field->decimals));

			if (!$column->hasColumnProperty(K::COLUMN_DATA_TYPE) &&
				Container::keyExists($field, 'type'))
				$column->setColumnProperty(K::COLUMN_DATA_TYPE,
					MySQLStatementBuilder::dataTypeFromMysqlType($field->type));

			if ($created)
				$map->setColumn($i, $column);
		}
	}

	public function __destruct()
	{
		$this->mysqlResult->free();
	}

	public function count()
	{
		return $this->mysqlResult->num_rows;
	}

	/**
	 *
	 * @return integer
	 */
	public function getColumnCount()
	{
		return $this->mysqlResult->field_count;
	}

	public function reset()
	{
		if ($this->mysqlResult->num_rows == 0)
			return false;

		$result = $this->mysqlResult->data_seek(0);
		if (!$result)
			return $result;

		return $this->mysqlResult->fetch_row();
	}

	protected function fetch($index)
	{
		return $this->mysqlResult->fetch_row();
	}

	protected function seekRecord($position)
	{
		$this->mysqlResult->data_seek($position);
	}

	/**
	 *
	 * @var \mysqli_result
	 */
	private $mysqlResult;
}