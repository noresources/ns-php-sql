<?php

namespace NoreSources\SQL;

use NoreSources as ns;

const kRecordModified = 0x1;
const kRecordExists = 0x2;

abstract class Record
{

	/**
	 *
	 * @param Table $table Table
	 */
	public function __construct(Table $table, $values)
	{
		$this->m_table = $table;
		$this->m_flags = 0;
		$this->m_value = array ();
		
		$structure = $table->getStructure();
		
		if (\is_array($values) || (\is_object($values) && ($values instanceof \ArrayAccess)))
		{
			foreach ($values as $key => $value)
			{
				if ($structure->offsetExists($key))
				{
					$this->m_values [$key] = $value;
				}
			}
		}
		elseif (\is_object($values) && ($values instanceof Recordset))
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
	}

	public function __get($member)
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

	public function __set($member, $value)
	{
		/**
		 * @todo check primary keys changes 
		 * @var unknown
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