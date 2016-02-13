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

	public function __construct($o, $v, $p = true)
	{
		$this->positive = $p;
		$this->operator = $o;
		$this->value = $v;
	}

	public function createExpression($className, TableField $f)
	{
		switch ($this->operator)
		{
			case '=':
			case 'in':
				return new SQLSmartEquality($f, call_user_func(array (
						$className,
						'serializeValue' 
				),$f->getName(), $this->value), !$this->positive);
				break;
			case 'between':
				if (!\is_array($this->value))
				{
					break;
				}
				if (count($this->value) != 2)
				{
					ns\Reporter::fatalError($this, __METHOD__ . ': Invalid between filter', __FILE__, __LINE__);
				}
				
				$min = call_user_func(array (
						$className,
						'unserializeValue' 
				), $this->value[0]);
				$max = call_user_func(array (
						$className,
						'unserializeValue' 
				), $this->value[1]);
				
				$e = new SQLBetween($f, $a_min, $a_max);
				if (!$this->positive)
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
				),$f->getName(), $this->value);
				return new ns\BinaryOperatorExpression(strtoupper($this->operator), $f, $f->importData($v));
				break;
		}
		
		return ns\Reporter::fatalError($this, __METHOD__ . ': Failed to create filter expression');
	}
}

class Record implements \ArrayAccess
{

	/**
	 *
	 * @param Table $table Database table
	 * @param mixed $filter Array of key-value pairs
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
	public static function get(Table $table, $filter, $flags = 0x00, $className = null)
	{
		if (!(is_string($className) && class_exists($className)))
		{
			$className = get_called_class();
		}
		
		$structure = $table->getStructure();
		if (!\is_array($filter))
		{
			if (!is_null($filter))
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
					return self::get($table, array (
							$primaryKeyColumn => $filter 
					), $flags, $className);
				}
			}
			elseif (!($flags & kRecordQueryMultiple))
			{
				return ns\Reporter::error(__CLASS__, __METHOD__ . ': $filter. Array expected');
			}
		}
		
		$s = new SelectQuery($table);
		
		/**
		 *
		 * @todo Accept IExpression or array of IExpression
		 */
		if (\is_array($filter))
		{
			foreach ($structure as $name => $column)
			{
				if (\array_key_exists($name, $filter))
				{
					$f = $table->fieldObject($name);
					$v = $filter[$name];
					if ($v instanceof ColumnFilter)
					{
						$e = $v->createExpression($className, $f);
						$s->where->addAndExpression($e);
					}
					else
					{
						$d = $f->importData(static::serializeValue($name, $v));
						$s->where->addAndExpression($f->equalityExpression($d));
					}
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

	public function __get($member)
	{
		return $this->offsetGet($member);
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
	 * @return boolean
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
				$f = $this->m_table->fieldObject($n);
				$d = $f->importData(static::serializeValue($n, $this->m_values[$n]));
				$i->addFieldValue($f, $d);
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
				$f = $this->m_table->fieldObject($n);
				$d = $f->importData(static::serializeValue($n, $this->m_values[$n]));
				if ($primary)
				{
					$u->where->addAndExpression($f->equalityExpression($d));
				}
				else
				{
					$count++;
					$u->addFieldValue($f, $d);
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
	 *
	 * @param integer $flags if kRecordQueryMultiple is set. The method may delete more than one record
	 * @return Number of deleted records or @c false if an error occurs
	 */
	public function delete($flags = 0x00)
	{
		if (!$multiple)
		{
			$record = static::get($table, $this->m_values, true);
			if (count($record) > 0)
			{
				return ns\Reporter::error($this, __METHOD__ . ': Multiple record deletion');
			}
		}
		
		$structure = $this->m_table->getStructure();
		$d = new DeleteQuery($this->m_table);
		foreach ($structure as $n => $c)
		{
			if (array_key_exists($n, $this->m_values))
			{
				$column = $this->m_table->fieldObject($n);
				$data = $column->importData(static::serializeValue($n, $this->m_values[$n]));
				$d->where->addAndExpression($column->equalityExpression($data));
			}
		}
		
		$result = $d->execute();
		$result = (is_object($result) && ($result instanceof DeleteQueryResult));
		if ($result)
		{
			$this->m_flags &= ~kRecordStateExists;
			return $result->affectedRowCount();
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

	public static function serializeValue($column, $value)
	{
		return $value;
	}

	public static function unserializeValue($column, $value)
	{
		return $value;
	}

	private function setValue(TableFieldStructure $f, $value, $unserialize = false)
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