<?php

namespace NoreSources\SQL;

use NoreSources as ns;
use SingingWire;

require_once (NS_PHP_CORE_PATH . '/arrays.php');

/**
 * The record values differs from the entry stored in the datasource
 * @var integer
 */
const kRecordStateModified = 0x01;

/**
 * A record with the same primary key values exists in the datasource
 * @var integer
 */
const kRecordStateExists = 0x02;

/**
 * Return or affect multiple records
 * @var integer
 */
const kRecordQueryMultiple = 0x04;

/**
 * When using getter methods,
 * create a new record if none can be found
 * @var integer
 */
const kRecordQueryCreate = 0x08;

/**
 * Indicate the input data are serialized
 * and should be unserialized
 * @var unknown
 */
const kRecordDataSerialized = 0x10;

class ColumnFilter
{

	public $columnName;

	/**
	 *
	 * @var string
	 */
	public $operator;

	/**
	 *
	 * @var mixed
	 */
	public $value;

	/**
	 *
	 * @var bool
	 */
	public $positive;

	/**
	 *
	 * @param mixel $column Column name or TableColumn
	 * @param string $operator Operator
	 * @param mixed $value Value
	 * @param boolean $positive
	 */
	public function __construct($column, $operator, $value, $positive = true)
	{
		$this->columnName = ($column instanceof TableColumn) ? $column->getName() : $column;
		$this->positive = $positive;
		$this->operator = $operator;
		$this->value = $value;
	}

	/**
	 *
	 * @param string $className Record object classname
	 * @param Table $table
	 */
	public function toExpression($className, Table $table)
	{
		return self::createExpression($className, $table, $this->operator, $this->value, $this->positive);
	}

	/**
	 *
	 * @param string $className
	 * @param Table $table
	 * @param string $operator
	 * @param mixed $value
	 * @param string $positive
	 * @return IExpression
	 */
	public function createExpression($className, Table $table, $operator, $value, $positive = true)
	{
		$column = $table->getColumn($this->columnName);
		switch ($operator)
		{
			case '=':
			case 'in':
				return new SQLSmartEquality($column, call_user_func(array (
						$className,
						'serializeValue' 
				), $column->getName(), $value), $positive);
				break;
			case 'between':
				if (!\is_array($value))
				{
					break;
				}
				if (count($value) != 2)
				{
					ns\Reporter::fatalError(__CLASS__, __METHOD__ . ': Invalid between filter', __FILE__, __LINE__);
				}
				
				$min = call_user_func(array (
						$className,
						'unserializeValue' 
				), $value[0]);
				$max = call_user_func(array (
						$className,
						'unserializeValue' 
				), $value[1]);
				
				$e = new SQLBetween($column, $a_min, $a_max);
				if (!$positive)
				{
					$e = new SQLNot($between);
				}
				
				return $e;
				
				break;
			case '<':
			case '<=':
			case '>':
			case '>=':
			case 'like':
				$v = call_user_func(array (
						$className,
						'serializeValue' 
				), $column->getName(), $value);
				$e = new ns\BinaryOperatorExpression(strtoupper($operator), $column, $column->importData($v));
				if (!$positive)
				{
					$e = new SQLNot($between);
				}
				
				return $e;
				break;
		}
		
		return ns\Reporter::fatalError(__CLASS__, __METHOD__ . ': Failed to create filter expression');
	}
}

class LimitFilter
{

	/**
	 *
	 * @var number
	 */
	public $limit;

	/**
	 *
	 * @var number
	 */
	public $offset;

	/**
	 *
	 * @param number $limit
	 * @param number $ooffset
	 */
	public function __construct($limit, $ooffset = 0)
	{
		$this->limit = $limit;
		$this->offset = $ooffset;
	}
}

class OrderFilter
{

	public $columnName;

	public $ascending;

	/**
	 *
	 * @param unknown $column Column name or TableColumn
	 * @param string $asc
	 */
	public function __construct($column, $asc = true)
	{
		$this->columnName = ($column instanceof TableColumn) ? $column->getName() : $column;
		$this->ascending = $asc;
	}
}

class Record implements \ArrayAccess
{

	/**
	 * Get or create a single record
	 * @param Table $table Table
	 * @param mixed $keys Primary key value. If the table primary key is composed of multiple keys, @param $key must be an array
	 * @param integer $flags Accepts kRecordQueryCreate
	 * @param string $className
	 */
	public static function getRecord(Table $table, $keys, $flags, $className = null)
	{
		if (!(is_string($className) && class_exists($className)))
		{
			$className = get_called_class();
		}
		
		$structure = $table->getStructure();
		
		if (is_null($keys))
		{
			return ns\Reporter::error(__CLASS__, __METHOD__ . ': Invalid key (null)');
		}
		
		if (!\is_array($keys))
		{
			$primaryKeyColumn = null;
			$primaryKeyColumns = $structure->getPrimaryKeyColumns();
			$c = count($primaryKeyColumns);
			if ($c == 0)
			{
				return ns\Reporter::error(__CLASS__, __METHOD__ . ': Table does not have primary key');
			}
			if ($c > 1)
			{
				return ns\Reporter::error(__CLASS__, __METHOD__ . ': Composite primary key can not accept non-array parameter');
			}
			
			list ( $pk, $_ ) = each($primaryKeyColumns);
			$keys = array (
					$pk => $keys 
			);
		}
		
		$s = new SelectQuery($table);
		foreach ($keys as $k => $v)
		{
			$column = $table->getColumn($k);
			$data = $column->importData(($flags & kRecordDataSerialized) ? $v : static::serializeValue($k, $v));
			$s->where->addAndExpression($column->equalityExpression($data));
		}
		
		$recordset = $s->execute();
		
		if (is_object($recordset) && ($recordset instanceof Recordset))
		{
			$c = $recordset->rowCount();
			if ($c == 1)
			{
				$result = new $className($table, $recordset, kRecordStateExists);
				return $result;
			}
			
			if ($c > 1)
			{
				return ns\Reporter::error(__CLASS__, __METHOD__ . ': Multiple record found');
			}
			
			if ($flags & kRecordQueryCreate)
			{
				$o = new $className($table, $keys, ($flags & kRecordDataSerialized));
				if ($o->insert())
				{
					return $o;
				}
				
				return false;
			}
			
			return null;
		}
		
		return ns\Reporter::error(__CLASS__, __METHOD__ . ': Invalid query result');
	}

	/**
	 *
	 * @param Table $table Database table
	 * @param mixed $filters Array of key-value pairs
	 *        If @param $table contains a single-column primary key, a singie value is accepted
	 *        as the value of the primary key column
	 * @param $flags
	 * @param mixed
	 *        If @c kRecordQueryMultiple is not set
	 *        - @c a Record if a single record is found
	 *        - @c null if no record can be found with the given criteria
	 *        - @c false if more than one record is found
	 *        otherwise
	 *        - an array of Record
	 */
	public static function queryRecord(Table $table, $filters, $flags = kRecordQueryMultiple, $className = null)
	{
		if (!(is_string($className) && class_exists($className)))
		{
			$className = get_called_class();
		}
		
		$structure = $table->getStructure();
		if (!\is_array($filters))
		{
			if (!is_null($filters))
			{
				$primaryKeyColumn = null;
				foreach ($structure as $n => $c)
				{
					if ($c->getProperty(kStructurePrimaryKey))
					{
						if (is_null($primaryKeyColumn))
						{
							$primaryKeyColumn = $n;
						}
						else
						{
							$primaryKeyColumn = null;
							break;
						}
					}
				}
				
				if (!is_null($primaryKeyColumn))
				{
					return self::queryRecord($table, array (
							$primaryKeyColumn => $filters 
					), $flags, $className);
				}
			}
			elseif (!($flags & kRecordQueryMultiple))
			{
				return ns\Reporter::error(__CLASS__, __METHOD__ . ': $filters. Array expected');
			}
		}
		
		$s = new SelectQuery($table);
		
		/**
		 *
		 * @todo Accept IExpression or array of IExpression
		 */
		if (\is_array($filters))
		{
			foreach ($filters as $name => $filter)
			{
				if ($filter instanceof ColumnFilter)
				{
					if (!$structure->offsetExists($filter->columnName))
					{
						continue;
					}
					
					$e = $filter->toExpression($className, $table);
					$s->where->addAndExpression($e);
				}
				elseif ($filter instanceof LimitFilter)
				{
					$s->limit($filter->offset, $filter->limit);
				}
				elseif ($filter instanceof OrderFilter)
				{
					if (!$structure->offsetExists($filter->columnName))
					{
						continue;
					}
					$column = $table->getColumn($filter->columnName);
					$s->orderBy->addColumn($column, $filter->ascending);
				}
				else
				{
					if (!$structure->offsetExists($name))
					{
						continue;
					}
					
					$column = $table->getColumn($name);
					$data = $column->importData(($flags & kRecordDataSerialized) ? $filter : static::serializeValue($name, $filter));
					$s->where->addAndExpression($column->equalityExpression($data));
				}
			}
		}
		
		$recordset = $s->execute();
		if (is_object($recordset) && ($recordset instanceof Recordset) && ($recordset->rowCount()))
		{
			if ($flags & kRecordQueryMultiple)
			{
				$result = array ();
				foreach ($recordset as $record)
				{
					$result[] = new $className($table, $record, (kRecordDataSerialized | kRecordStateExists));
				}
				
				return $result;
			}
			else
			{
				if ($recordset->rowCount() == 1)
				{
					$result = new $className($table, $recordset, kRecordStateExists);
					return $result;
				}
				
				return ns\Reporter::error(__CLASS__, __METHOD__ . ': Non unique result', __FILE__, __LINE__);
			}
		}
		
		return (($flags & kRecordQueryMultiple) ? array () : null);
	}

	/**
	 *
	 * @param Table $table
	 * @param unknown $values Column name-value pairs
	 * @param number $flags
	 * @param string $className Record object classname
	 * @return Record|boolean The newly created Record on success
	 *         @c false otherwise
	 */
	public static function createRecord(Table $table, $values, $flags = 0, $className = null)
	{
		if (!(is_string($className) && class_exists($className)))
		{
			$className = get_called_class();
		}
		
		$o = new $className($table, $values, ($flags & kRecordDataSerialized));
		if ($o->insert())
		{
			return $o;
		}
		
		return false;
	}

	/**
	 *
	 * @param Table $table Table
	 */
	public function __construct(Table $table, $values, $flags = 0)
	{
		$this->m_table = $table;
		$this->m_flags = ($flags & (kRecordStateExists | kRecordStateModified));
		$this->m_values = array ();
		
		$structure = $table->getStructure();
		
		if (\is_object($values) && ($values instanceof Recordset))
		{
			$this->m_flags |= kRecordStateExists;
			$values = $values->current();
			foreach ($values as $key => $value)
			{
				if (is_string($key) && $structure->offsetExists($key))
				{
					$this->setValue($structure->offsetGet($key), $value, true);
				}
			}
		}
		elseif (\is_array($values) || (\is_object($values) && ($values instanceof \ArrayAccess)))
		{
			foreach ($values as $key => $value)
			{
				if ($structure->offsetExists($key))
				{
					$this->setValue($structure->offsetGet($key), $value, ($flags & kRecordDataSerialized));
				}
			}
		}
	}

	public function offsetGet($member)
	{
		$structure = $this->m_table->getStructure();
		if ($structure->offsetExists($member))
		{
			if (array_key_exists($member, $this->m_values))
			{
				return $this->m_values[$member];
			}
			
			return null;
		}
		
		throw new \InvalidArgumentException('Invalid member ' . $member);
	}

	public function offsetExists($offset)
	{
		return $this->m_table->getStructure()->offsetExists($offset);
	}

	public function offsetUnset($offset)
	{
		unset($this->m_values[$offset]);
		$this->m_flags |= kRecordStateModified;
	}

	public function offsetSet($member, $value)
	{
		/**
		 *
		 * @todo check primary keys changes
		 */
		$structure = $this->m_table->getStructure();
		if ($structure->offsetExists($member))
		{
			$c = $structure->offsetGet($member);
			$this->setValue($c, $value);
			
			$this->m_flags |= kRecordStateModified;
			return;
		}
		
		throw new \InvalidArgumentException('Invalid member ' . $member);
	}

	public function __get($member)
	{
		return $this->offsetGet($member);
	}

	/**
	 * Set a column value
	 * @param string $member
	 * @param mixed $value
	 */
	public function __set($member, $value)
	{
		return $this->offsetSet($member, $value);
	}

	/**
	 * Convert Record to a regular PHP array
	 * @return array
	 */
	public function toArray()
	{
		return $this->m_values;
	}

	/**
	 *
	 * @return boolean @c true if the record was successfully inserted
	 */
	public function insert()
	{
		$structure = $this->m_table->getStructure();
		$i = new InsertQuery($this->m_table);
		$autoIncrementColumn = null;
		
		foreach ($structure as $n => $c)
		{
			if ($c->getProperty(kStructureAutoincrement))
			{
				$autoIncrementColumn = $c;
			}
			
			if (\array_key_exists($n, $this->m_values) && (is_null($autoIncrementColumn) || ($autoIncrementColumn->getName() != $n)))
			{
				$column = $this->m_table->getColumn($n);
				$data = $column->importData(static::serializeValue($n, $this->m_values[$n]));
				$i->addColumnValue($column, $data);
			}
		}
		
		$result = $i->execute();
		if (is_object($result) && ($result instanceof InsertQueryResult))
		{
			if (!is_null($autoIncrementColumn))
			{
				$this->setValue($autoIncrementColumn, $result->lastInsertId());
			}
			
			$this->m_flags |= kRecordStateExists;
			$this->m_flags &= ~kRecordStateModified;
			return true;
		}
		
		return false;
	}

	/**
	 *
	 * @return boolean
	 */
	public function update()
	{
		if (!($this->m_flags & kRecordStateExists))
		{
			return $this->insert();
		}
		
		$structure = $this->m_table->getStructure();
		$u = new UpdateQuery($this->m_table);
		$count = 0;
		foreach ($structure as $n => $c)
		{
			$primary = $c->getProperty(kStructurePrimaryKey);
			if (array_key_exists($n, $this->m_values))
			{
				$column = $this->m_table->getColumn($n);
				$data = $column->importData(static::serializeValue($n, $this->m_values[$n]));
				if ($primary)
				{
					$u->where->addAndExpression($column->equalityExpression($data));
				}
				else
				{
					$count++;
					$u->addColumnValue($column, $data);
				}
			}
			elseif ($primary)
			{
				return ns\Reporter::error($this, __METHOD__ . ': Missing value for primary key column ' . $n, __FILE__, __LINE__);
			}
		}
		
		if ($count == 0)
		{
			ns\Reporter::notice($this, __METHOD__ . ': Nothing to update', __FILE__, __LINE__);
			$this->m_flags &= ~kRecordStateModified;
			return true;
		}
		
		$result = $u->execute();
		
		if (is_object($result) && ($result instanceof UpdateQueryResult))
		{
			$this->m_flags &= ~kRecordStateModified;
			return true;
		}
		
		return false;
	}

	/**
	 * Delete the record
	 * @param integer $flags Option flags
	 *        If @c kRecordQueryMultiple is set, the method may delete more than one record.
	 *        Otherwise the method will use the primary key column(s) to identify a unique record
	 * @return Number of deleted records or @c false if an error occurs
	 */
	public function delete($flags = 0x00)
	{
		$structure = $this->m_table->getStructure();
		$columns = array ();
		$usePrimaryKeys = false;
		
		if ($flags & kRecordQueryMultiple)
		{
			$columns = $structure->getIterator();
		}
		else
		{
			$columns = $structure->getPrimaryKeyColumns();
			$usePrimaryKeys = true;
			if (count($columns) == 0)
			{
				// Table does not have primary key -> check if the delete command will
				$records = self::queryRecord($this->m_table, $this->m_values, $flags | kRecordQueryMultiple);
				if (count($records) > 1)
				{
					return ns\Reporter::error($this, __METHOD__ . ': Multiple records match the current record values');
				}
				
				// We accept to work with all columns
				$columns = $structure->getIterator();
				$usePrimaryKeys = false;
			}
		}
		
		$d = new DeleteQuery($this->m_table);
		foreach ($columns as $n => $c)
		{
			if (array_key_exists($n, $this->m_values))
			{
				$column = $this->m_table->getColumn($n);
				$data = $column->importData(static::serializeValue($n, $this->m_values[$n]));
				$d->where->addAndExpression($column->equalityExpression($data));
			}
			elseif ($usePrimaryKeys)
			{
				return ns\Reporter::error($this, __METHOD__ . ': Missing required column ' . $n . ' value');
			}
		}
		
		$result = $d->execute();
		if (is_object($result) && ($result instanceof DeleteQueryResult))
		{
			$this->m_flags &= ~kRecordStateExists;
			return ($result->affectedRowCount() > 0);
		}
		
		return false;
	}

	/**
	 *
	 * @return \NoreSources\SQL\Table
	 */
	public function getTable()
	{
		return $this->m_table;
	}

	public function getKey($glue = null)
	{
		$key = array ();
		foreach ($this->m_table->getStructure() as $n => $c)
		{
			if ($c->getProperty(kStructurePrimaryKey))
			{
				if (!array_key_exists($n, $this->m_values))
				{
					return ns\Reporter::fatalError($this, __METHOD__ . ': Incomplete key');
				}
				
				$key[$n] = $this->m_values[$n];
			}
		}
		
		if (is_string($glue))
		{
			$key = implode($glue, $key);
		}
		
		return $key;
	}

	public static function serializeValue($column, $value)
	{
		return $value;
	}

	public static function unserializeValue($column, $value)
	{
		return $value;
	}

	public static function recordsetToArray(Recordset $records)
	{
		$result = array ();
		foreach ($records as $record)
		{
			$table = array ();
			foreach ($record as $column => $value)
			{
				if (is_numeric($column))
				{
					continue;
				}
				
				$table[$column] = static::unserializeValue($column, $value);
			}
			
			$result[] = $table;
		}
		
		return $result;
	}

	private function setValue(TableColumnStructure $f, $value, $unserialize = false)
	{
		if ($unserialize)
		{
			$value = static::unserializeValue($f->getName(), $value);
		}
		elseif (is_numeric($value) && ($f->getProperty(kStructureDatatype) == kDataTypeNumber))
		{
			if ($f->getProperty(kStructureDecimalCount) > 0)
			{
				$value = floatval($value);
			}
			else
			{
				$value = intval($value);
			}
		}
		
		$this->m_values[$f->getName()] = $value;
	}

	/**
	 *
	 * @var Table
	 */
	private $m_table;

	/**
	 *
	 * @var integer
	 */
	private $m_flags;

	/**
	 *
	 * @var array
	 */
	private $m_values;
}