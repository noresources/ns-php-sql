<?php

namespace NoreSources\SQL;

use NoreSources\Reporter;
use NoreSources as ns;

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

abstract class Record implements \ArrayAccess
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
		 * @todo Accept IExpression or array of IExpression
		 */
		if (\is_array($filter))
		{
			foreach ($structure as $name => $column)
			{
				if (\array_key_exists($name, $filter))
				{
					$f = $table->fieldObject($name);
					$d = $f->importData($filter[$name]);
					$s->where->addAndExpression($f->equalityExpression($d));
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
					$result[] = new $className($table, $record, kRecordStateExists);
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
	 * Insert or update record
	 * @param Table $table
	 * @param array $keyValues array of key-value pairs
	 * @param boolean $returnRecord If @c true, return the inserted or updated @c Record
	 * @return mixed - boolean if $returnRecord is @c false
	 *         - Record if $returnRecord is @c true and operation succeeded
	 *         - @ca false otherwise
	 */
	public static function upsert(Table $table, $keyValues, $returnRecord = true, $className = null)
	{
		if (!(is_string($className) && class_exists($className)))
		{
			$className = get_called_class();
		}
		
		$structure = $table->getStructure();
		$primaryKevValues = array ();
		foreach ($structure as $n => $c)
		{
			if (array_key_exists($n, $keyValues))
			{
				$primaryKevValues[$n] = $keyValues[$n];
			}
		}
		
		$record = self::get($table, $primaryKevValues, 0x0, $className);
		$result = false;
		if ($record)
		{
			foreach ($structure as $n => $c)
			{
				if (array_key_exists($n, $keyValues))
				{
					$record->$n = $keyValues[$n];
				}
			}
			
			$result = $record->update();
		}
		else
		{
			$record = new $className($table, $keyValues, 0);
			$result = $record->insert();
		}
		
		if ($returnRecord)
		{
			return (($result) ? $record : false);
		}
		
		return $result;
	}

	/**
	 *
	 * @param Table $table Table
	 */
	public function __construct(Table $table, $values, $flags = 0)
	{
		$this->m_table = $table;
		$this->m_flags = $flags;
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
					$this->setValue($structure->offsetGet($key), $value);
				}
			}
		}
		elseif (\is_array($values) || (\is_object($values) && ($values instanceof \ArrayAccess)))
		{
			foreach ($values as $key => $value)
			{
				if ($structure->offsetExists($key))
				{
					$this->setValue($structure->offsetGet($key), $value);
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

	public function __set($member, $value)
	{
		return $this->offsetSet($member, $value);
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
				$d = $f->importData($this->m_values[$n]);
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
			return $this->inserrt();
		}
		
		$structure = $this->m_table->getStructure();
		$u = new UpdateQuery($this->m_table);
		
		foreach ($structure as $n => $c)
		{
			$primary = $c->getProperty(kStructurePrimaryKey);
			if (array_key_exists($n, $this->m_values))
			{
				if ($primary)
				{
					$f = $this->m_table->fieldObject($n);
					$d = $f->importData($this->m_values[$n]);
					$u->where->addAndExpression($f->equalityExpression($d));
				}
				else
				{
					$u->addFieldValue($c, $this->m_values[$n]);
				}
			}
			elseif ($primary)
			{
				return ns\Reporter::error($this, __METHOD__ . ': Missing value for primary key column ' . $n, __FILE__, __LINE__);
			}
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
				$data = $column->importData($this->m_values[$n]);
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

	private function setValue(TableFieldStructure $f, $value)
	{
		if (is_numeric($value) && ($f->getProperty(kStructureDatatype) == kDataTypeNumber))
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