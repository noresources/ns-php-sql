<?php

namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\SQL\Constants as K;

class ParameterArray implements \IteratorAggregate
{
	const VALUE = 'value';
	const TYPE = 'type';

	public function getIterator()
	{
		return $this->table->getIterator();
	}

	public function set($parameter, $value, $type = K::DATATYPE_UNDEFINED)
	{
		if ($type == K::DATATYPE_UNDEFINED)
		{
			$type = K::DATATYPE_STRING;
			if (is_bool($value))
				$type = K::DATATYPE_BOOLEAN;
			elseif (is_float($value))
			$type = K::DATATYPE_FLOAT;
			elseif (is_int($value))
				$type = K::DATATYPE_INTEGER;
			elseif (is_null($value))
				$type = K::DATATYPE_NULL;
		}

		$this->table->offsetSet($parameter, array (
				self::VALUE => $value,
				self::TYPE => $type
		));
	}

	public function clear()
	{
		$this->table->exchangeArray(array ());
	}

	public function __construct($table = array ())
	{
		$this->table = new \ArrayObject();

		foreach ($table as $key => $value)
		{
			$tyoe = K::DATATYPE_UNDEFINED;
			if (ns\ContainerUtil::isArray($value))
			{
				$type = ns\ContainerUtil::keyValue($value, self::TYPE, K::DATATYPE_UNDEFINED);
				$value = ns\ContainerUtil::keyValue($value, self::VALUE, null);
			}
			
			$this->set($key, $value, $tyoe);
		}
	}

	/**
	 * @var \ArrayObject
	 */
	private $table;
}

abstract class PreparedStatement
{

	public function __construct (StatementContext $context)
	{
		$this->parameters = $context->getParameters();
	}
	
	public function __toString()
	{
		return $this->getStatement();
	}
	
	/**
	 * @return array Array of NoreSources\SQL\StatementContextParameter
	 */
	public function getParameters()
	{
		return $this->parameters;
	}
	
	/**
	 * @return string SQL statement string
	 */
	abstract function getStatement();

	/**
	 * @return integer Number of parameters
	 */
	public function getParameterCount()
	{
		$c = 0;
		foreach ($this->parameters as $p) 
		{
			$c += ns\ContainerUtil::count ($p->indexes);
		}
		return $c;
	}
	
	private $parameters;	
}