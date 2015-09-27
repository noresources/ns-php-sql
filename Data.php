<?php

/**
 * Copyright © 2012-2015 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;
use NoreSources as ns;

require_once ('base.php');
require_once (NS_PHP_CORE_PATH . '/Expressions.php');

use \InvalidArgumentException;
use \Exception;
use \DateTime;

/**
 * Record value
 */
abstract class Data implements ns\IExpression
{
	/**
	 * The data is valid and can be recorded to daatasource
	 * 
	 * @var integer
	 */
	const kValid = 0x1;
	/**
	 * The data accept NULL as a valid value
	 * 
	 * @var integer
	 */
	const kAcceptNull = 0x2;
	/**
	 * The data has a minimal value (number data)
	 * 
	 * @var integer
	 */
	const kBoundaryMin = 0x4;
	
	/**
	 * The data has a maximal value (number data)
	 * 
	 * @var integer
	 */
	const kBoundaryMax = 0x8;

	protected function __construct($type)
	{
		$this->m_type = $type;
		$this->m_flags = 0;
	}

	/**
	 * Read only access to some private members
	 * 
	 * @param string $member
	 *        	Member name (flags, type or value)
	 * @throws InvalidArgumentException
	 */
	public function __get($member)
	{
		if ($member == 'flags')
		{
			return $this->m_flags;
		}
		elseif ($member == 'type')
		{
			return $this->m_type;
		}
		elseif ($member == 'valid')
		{
			return (($this->m_flags & self::kValid) == self::kValid);
		}
		elseif ($member == 'value')
		{
			return $this->getValue();
		}
		
		throw new \InvalidArgumentException($member);
	}

	/**
	 * Load arbitrary data to be written in a SQL record
	 * 
	 * @param mixed $value        	
	 * @return <code>true</code> if the value can be imported
	 */
	abstract function import($value);

	/**
	 *
	 * @return mixed The current value
	 */
	abstract function getValue();

	protected function check()
	{
		if (!($this->m_flags & self::kValid))
		{
			throw new Exception('Invalid Data ' . var_export($this->value, true));
		}
		
		return true;
	}

	protected function importResult($value)
	{
		if ($value)
		{
			$this->m_flags |= self::kValid;
		}
		else
		{
			$this->m_flags &= ~self::kValid;
		}
		
		return (($value) ? true : false);
	}

	protected function setFlags($flags)
	{
		$this->m_flags = $flags;
	}

	private $m_flags;

	private $m_type;
}

/**
 * Null (nil) value
 *
 * A Null data is always valid and can't be changed
 */
class NullData extends Data
{

	public function __construct(Datasource $datasource)
	{
		parent::__construct(kDataTypeNull);
		$this->setFlags($this->flags | self::kValid);
		$this->m_nullKeyword = $datasource->getDatasourceString(Datasource::kStringKeywordNull);
	}

	public function import($data)
	{
		return $this->importResult(true);
	}

	public function getValue()
	{
		return null;
	}

	public function expressionString($options = null)
	{
		return $this->m_nullKeyword;
	}

	private $m_nullKeyword;
}

/**
 * Boolean value
 */
class BooleanData extends Data
{

	public function __construct(Datasource $datasource)
	{
		parent::__construct(kDataTypeBoolean);
		$this->m_datasource = $datasource;
	}

	public function import($data)
	{
		$this->m_value = $data;
		return $this->importResult(true);
	}

	public function getValue()
	{
		return $this->m_value;
	}

	public function expressionString($options = null)
	{
		$this->check();
		return $this->m_datasource->getDatasourceString((($this->m_value) ? Datasource::kStringKeywordTrue : Datasource::kStringKeywordFalse));
	}

	private $m_value;

	private $m_datasource;
}

class FormattedData extends Data
{

	public function __construct($data = null)
	{
		parent::__construct(kDataTypeString);
		$this->m_value = $data;
		$this->importResult(true);
	}

	public function import($data)
	{
		$this->m_value = $data;
		return $this->importResult(true);
	}

	public function getValue()
	{
		return $this->m_value;
	}

	public function expressionString($options = null)
	{
		return $this->m_value;
	}

	private $m_value;
}

/**
 * Text value
 */
class StringData extends Data
{

	/**
	 *
	 * @param Datasource $datasource        	
	 * @param SQLTableFieldStructure $structure        	
	 */
	public function __construct(Datasource $datasource, /*SQLTableFieldStructure*/ $structure)
	{
		parent::__construct(kDataTypeString);
		$this->m_datasource = $datasource;
	}

	public function __get($member)
	{
		if ($member == 'datasource')
		{
			return $this->m_datasource;
		}
		
		return parent::__get($member);
	}

	public function import($data)
	{
		if (is_string($data))
		{
			$this->m_value = $data;
			return $this->importResult(true);
		}
		
		return $this->importResult(false);
	}

	public function expressionString($options = null)
	{
		$this->check();
		
		if ($this->m_value === null)
		{
			return $this->m_datasource->getDatasourceString(Datasource::kStringKeywordNull);
		}
		
		return protectString($this->m_value);
	}

	public function getValue()
	{
		return $this->m_value;
	}

	/**
	 *
	 * @return The string value as it should appear in SQL statement
	 */
	protected function getDatasourceStringExpression()
	{
		return protect($this->m_value);
	}

	private $m_datasource;

	private $m_value;
}

/**
 * Number value
 */
class NumberData extends Data
{

	/**
	 * Miniimum value (inclusive)
	 *
	 * @var number
	 */
	public $min;

	/**
	 * Maximum value (inclusive)
	 *
	 * @var integer
	 */
	public $max;

	/**
	 * Decimal count
	 *
	 * @var integer
	 */
	public $decimals;

	/**
	 *
	 * @param Datasource $datasource        	
	 * @param SQLTableFieldStructure $structure        	
	 */
	public function __construct(Datasource $datasource, /*SQLTableFieldStructure*/ $structure)
	{
		parent::__construct(kDataTypeNumber);
		$this->m_value = null;
		$this->m_datasource = $datasource;
		$this->setFlags($this->flags | self::kAcceptNull);
		/*
		 * @todo handle structure info
		 */
	}

	public function expressionString($options = null)
	{
		$this->check();
		
		$data = $this->m_value;
		
		if ($this->m_value === null)
		{
			return $this->m_datasource->getDatasourceString(Datasource::kStringKeywordNull);
		}
		
		if ($this->decimals > 0)
		{
			$data = floatval($data);
		}
		else
		{
			$data = intval($data);
		}
		
		if ($this->flags & self::kBoundaryMin)
		{
			$data = max($this->min, $data);
		}
		
		if ($this->flags & self::kBoundaryMax)
		{
			$data = min($this->max, $data);
		}
		
		return $data;
	}

	public function import($data)
	{
		if ($data === null)
		{
			if ($this->flags & self::kAcceptNull)
			{
				$this->m_value = $data;
			}
			else // convert to empty string
			{
				$this->m_value = '';
			}
			
			return $this->importResult(true);
		}
		
		if (is_bool($data))
		{
			$data = (($data) ? 1 : 0);
		}
		elseif (!is_numeric($data))
		{
			return $this->importResult(false);
		}
		
		$this->m_value = $data;
		return $this->importResult(true);
	}

	public function getValue()
	{
		return $this->m_value;
	}

	private $m_datasource;

	private $m_value;
}

/**
 * Date and time
 */
class TimestampData extends Data
{

	/**
	 *
	 * @param Datasource $datasource        	
	 * @param SQLTableFieldStructure $structure        	
	 */
	public function __construct(Datasource $datasource, /*SQLTableFieldStructure*/ $structure)
	{
		parent::__construct(kDataTypeTimestamp);
		$this->setFlags($this->flags | self::kValid);
		$this->m_dateTime = new DateTime();
	}

	/**
	 *
	 * @param mixed $data
	 *        	DateTime, integer (UNIX timestamp),
	 *        	string (compliant with DateTime constructor),
	 *        	or an array containing {format, time} using 'format'/0 and 'time'/1 keys
	 */
	public function import($data)
	{
		if ($data instanceof DateTime)
		{
			$this->m_dateTime = clone $data;
		}
		elseif (is_numeric($data))
		{
			$v = intval($data);
			$this->m_dateTime->setTimestamp($v);
		}
		elseif (is_string($data))
		{
			// Assumes format is a php-compliant format
			$this->m_dateTime = new DateTime($data);
		}
		elseif (is_array($data) && (count($data) == 2))
		{
			if (array_key_exists('format', $data) && array_key_exists('time', $data))
			{
				$this->m_dateTime = DateTime::createFromFormat($data ['format'], $data ['time']);
			}
			elseif (array_key_exists(0, $data) && array_key_exists(1, $data))
			{
				$this->m_dateTime = DateTime::createFromFormat($data [0], $data [1]);
			}
			else
			{
				return $this->importResult(false);
			}
		}
		else
		{
			return $this->importResult(false);
		}
		
		return $this->importResult(true);
	}

	public function getValue()
	{
		return $this->m_dateTime;
	}

	public function expressionString($options = null)
	{
		$this->check();
		$fmtString = $this->m_datasource->getDatasourceString(Datasource::kStringTimestampFormat);
		$fmt = $this->m_datasource->getDatasourceString($fmtString);
		return protectString($this->m_dateTime->format($fmt));
	}

	/**
	 *
	 * @var Datasource
	 */
	private $m_datasource;

	/**
	 *
	 * @var DateTime
	 */
	private $m_dateTime;
}

class BinaryData extends Data
{

	public function __construct(Datasource $datasource, SQLTableFieldStructure $structure)
	{
		parent::__construct(kDataTypeBinary);
		$this->setFlags($this->flags | self::kAcceptNull);
		$this->m_datasource = $datasource;
	}

	public function import($data)
	{
		if (($data === null) && !($this->flags & self::kAcceptNull))
		{
			return $this->importResult(false);
		}
		
		$this->m_value = $data;
		return $this->importResult(true);
	}

	public function expressionString($options = null)
	{
		$this->check();
		
		if ($this->m_value === null)
		{
			return $this->m_datasource->getDatasourceString(Datasource::kStringKeywordNull);
		}
		
		return $this->getDatasourceBinaryExpression();
	}

	public function getValue()
	{
		return $this->m_value;
	}

	protected function getDatasourceBinaryExpression()
	{
		return protect($this->m_value);
	}	

	private $m_datasource;

	private $m_value;
}
