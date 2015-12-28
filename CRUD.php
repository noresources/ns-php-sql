<?php

namespace NoreSources\SQL;

use NoreSources\Reporter;

use NoreSources as ns;

require_once (NS_PHP_CORE_PATH . '/arrays.php');

const kRecordModified = 0x1;
const kRecordExists = 0x2;

abstract class Record implements \ArrayAccess
{

	/**
	 * @param Table $table Database table
	 * @param mixed $keyValues Array of key-value pairs
	 * 	If @param $table contains a single-column primary key, a singie value is accepted
	 * 	as the value of the primary key column
	 * @param boolean $multiple If @c true, always return result as an array of Record
	 * @param mixed
	 * - @c null if no record can be found with the given criteria
	 * - @c false if @param $multiple is @c false ans query returns more than one element
	 * - a @c Record if @param $multiple is @c false
	 * - an array of @c Record if @param $multiple is @c true
	 */
	public static function get (Table $table, $keyValues, $multiple = false, $className = null)
	{
		$structure = $table->getStructure();
		if (!is_array ($keyValues))
		{
			if (is_string($keyValues) || is_numeric($keyValues))
			{
				$primaryKeyColumn = null;
				foreach ($structure as $n => $c)
				{
					if ($c->getProperty (kStructurePrimaryKey))
					{
						if (is_null ($primaryKeyColumn))
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
					return static::get ($table, array ($primaryKeyColumn => $keyValues, $multiple, $className));
				}
			}

			return ns\Reporter::error(__CLASS__, __METHOD__ . ': $keyValues. Array expected');
		}

		if (!(is_string ($className) && class_exists($className)))
		{
			$className = static::getRecordClassName();
		}

		$s = new SelectQuery($table);

		if (is_array ($keyValues))
		{
			foreach ($structure as $name => $column)
			{
				if (array_key_exists($name, $keyValues))
				{
					$f = $table->fieldObject($name);
					$d = $f->importData ($keyValues[$name]);
					$s->where->addAndExpression ($f->equalityExpression ($d));
				}
			}
		}

		$recordset = $s->execute();
		if (is_object ($recordset) && ($recordset instanceof Recordset) && ($recordset->rowCount()))
		{
			if ($multiple)
			{
				$result = array ();
				foreach ($recordset as $record)
				{
					$result[]= new $className ($table, $record, kRecordExists);
				}

				return $result;
			}
			else
			{
				if ($recordset->rowCount() == 1)
				{
					$result = new $className ($table, $recordset, kRecordExists);
					return $result;
				}

				return ns\Reporter::error(__CLASS__, __METHOD__ . ': Non unique result', __FILE__, __LINE__);
			}
		}

		return null;
	}

	/**
	 * Insert or update record
	 * @param Table $table
	 * @param array $keyValues array of key-value pairs
	 * @param boolean $returnRecord If @c true, return the inserted or updated @c Record
	 * @return mixed
	 * 	- boolean if $returnRecord is @c false
	 *  - Record if $returnRecord is @c true and operation succeeded
	 *  - @ca false otherwise
	 */
	public static function upsert (Table $table, $keyValues, $returnRecord = true)
	{
		$structure = $table->getStructure();
		$primaryKevValues = array ();
		foreach ($structure as $n => $c)
		{
			if (array_key_exists($n, $keyValues))
			{
				$primaryKevValues[$n] = $keyValues[$n];
			}
		}

		$record = get($table, $primaryKevValues, false, static::getRecordClassName());
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
			$record = new $className ($table, $keyValues, 0);
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
			$this->m_flags |= kRecordExists;
			$values = $values->current();
			foreach ($values as $key => $value)
			{
				if (is_string($key) && $structure->offsetExists($key))
				{
					$this->m_values [$key] = $value;
				}
			}
		}
		elseif (\is_array($values) || (\is_object($values) && ($values instanceof \ArrayAccess)))
		{
			foreach ($values as $key => $value)
			{
				if ($structure->offsetExists($key))
				{
					$this->m_values [$key] = $value;
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
				return $this->m_values [$member];
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
		unset ($this->m_values[$offset]);
		$this->m_flags |= kRecordModified;
	}

	public function __set($member, $value)
	{
		return $this->offsetSet($member, $value);
	}
	
	public function offsetSet ($member, $value)
	{
		/**
		 * @todo check primary keys changes
		 */

		$structure = $this->m_table->getStructure();
		if ($structure->offsetExists($member))
		{
			$this->m_values [$member] = $value;
			$this->m_flags |= kRecordModified;
			return;
		}

		throw new \InvalidArgumentException('Invalid member ' . $member);
	}
	
	/**
	 * @return boolean
	 */
	public function insert ()
	{
		$structure = $this->m_table->getStructure();
		$i = new InsertQuery($this->m_table);
		$autoIncrementColumn = null;
		
		foreach ($structure as $n => $c)
		{
			if ($c->getProperty (kStructureAutoincrement))
			{
				$autoIncrementColumn = $n;
			}
			
			if (array_key_exists($n, $this->m_values) && ($autoIncrementColumn != $n))
			{
				$i->addFieldValue($n, $this->m_values[$n]);
			}
		}
		
		$result = $i->execute();
		if (is_object ($result) && ($result instanceof InsertQueryResult))
		{
			if (!is_null($autoIncrementColumn))
			{
				$this->m_values[$autoIncrementColumn] = $result->lastInsertId();
			}
			
			$this->m_flags |= kRecordExists;
			$this->m_flags &= ~kRecordModified;
			return true;
		}
		
		return false;
	}
	
	/**
	 * @return boolean
	 */
	public function update ()
	{
		if (!($this->m_flags & kRecordExists))
		{
			return $this->inserrt();
		}
		
		$structure = $this->m_table->getStructure();
		$u = new UpdateQuery($this->m_table);
		
		foreach ($structure as $n => $c)
		{
			$primary = $c->getProperty (kStructurePrimaryKey);
			if (array_key_exists($n, $this->m_values))
			{
				if ($primary)
				{
					$f = $this->m_table->fieldObject($n);
					$d = $f->importData ($this->m_values[$n]);
					$u->where->addAndExpression ($f->equalityExpression ($d));
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
		
		if (is_object ($result) && ($result instanceof UpdateQueryResult))
		{
			$this->m_flags &= ~kRecordModified;
			return true;
		}
		
		return false;
	}
	
	/**
	 * @param boolean $multiple Accept to delete more than one record
	 * @return boolean
	 */
	public function delete ($multiple = false)
	{
		if (!$multiple)
		{
			$record = static::get ($table, $this->m_values, true);
			if (count ($record) > 0)
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
			$this->m_flags &= ~kRecordExists;
		}
		
		return false;
	}
	
	protected function getRecordClassName ()
	{
		return (__CLASS__);
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