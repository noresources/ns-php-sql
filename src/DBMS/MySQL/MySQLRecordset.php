<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\Container\Container;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\MySQL\MySQLConstants as K;
use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Result\Traits\SeekableRecordsetTrait;
use NoreSources\SQL\Structure\ArrayColumnDescription;

/**
 *
 * @todo Seekable MySQL recordset
 *
 */
class MySQLRecordset extends Recordset implements \Countable,
	\SeekableIterator
{

	use SeekableRecordsetTrait;

	public function __construct(\mysqli_result $result,
		ConnectionInterface $connection, $data = null)
	{
		parent::__construct($connection, $data);

		$types = $connection->getPlatform()->getTypeRegistry();

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
				$column = $map->get($i);
			else
			{
				$created = true;
				$column = new ArrayColumnDescription();
			}

			if ($types->has($field->type))
			{
				/** @var \NoreSources\SQL\DBMS\TypeInterface $type */
				$type = $types->get($field->type);
				$dataType = $type->getDataType();
				$column->setColumnProperty(K::COLUMN_TYPE_NAME,
					$type->getTypeName());
				if (($dataType & ~K::DATATYPE_NULL) > 0)
					$column->setColumnProperty(K::COLUMN_DATA_TYPE,
						$dataType);
			}

			if (!$column->has(K::COLUMN_NAME) ||
				empty($column->getName()))
				$column->setColumnProperty(K::COLUMN_NAME,
					Container::keyValue($field, 'name',
						Container::keyValue($field, 'orgname')));

			if (!$column->has(K::COLUMN_FRACTION_SCALE) &&
				Container::keyExists($field, 'decimals'))
				$column->setColumnProperty(K::COLUMN_FRACTION_SCALE,
					intval($field->decimals));

			if (!$column->has(K::COLUMN_DATA_TYPE) &&
				Container::keyExists($field, 'type'))
				$column->setColumnProperty(K::COLUMN_DATA_TYPE,
					MySQLConnection::dataTypeFromMysqlType($field->type));

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