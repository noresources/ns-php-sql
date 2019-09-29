<?php
namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\SQL\Constants as K;

class ParameterArray implements \IteratorAggregate, \Countable
{

	const VALUE = 'value';

	const TYPE = 'type';

	public function getIterator()
	{
		return $this->table->getIterator();
	}

	public function count()
	{
		return $this->table->count();
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

		$this->table->offsetSet($parameter, [
			self::VALUE => $value,
			self::TYPE => $type
		]);
	}

	public function clear()
	{
		$this->table->exchangeArray([]);
	}

	public function __construct($table = [])
	{
		$this->table = new \ArrayObject();

		foreach ($table as $key => $value)
		{
			$tyoe = K::DATATYPE_UNDEFINED;
			if (ns\Container::isArray($value))
			{
				$type = ns\Container::keyValue($value, self::TYPE, K::DATATYPE_UNDEFINED);
				$value = ns\Container::keyValue($value, self::VALUE, null);
			}

			$this->set($key, $value, $tyoe);
		}
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $table;
}

abstract class PreparedStatement
{

	/**
	 *
	 * @param string|StatementData $data
	 */
	public function __construct($data)
	{
		if ($data instanceof StatementData)
			$this->parameters = $data->parameters;
		else
			$this->parameters = new StatementParameterMap();
	}

	public function __toString()
	{
		return $this->getStatement();
	}

	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 *
	 * @param StatementParameterMap $parameters
	 * @return \NoreSources\SQL\PreparedStatement
	 */
	public function setParameters(StatementParameterMap $parameters)
	{
		$this->parameters = $parameters;
		return $this;
	}

	/**
	 *
	 * @return string SQL statement string
	 */
	abstract function getStatement();

	/**
	 *
	 * @return integer Number of parameters
	 */
	public function getParameterCount()
	{
		return $this->parameters->count();
	}

	/**
	 *
	 * @var StatementParameterMap
	 */
	private $parameters;
}