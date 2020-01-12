<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\SQL\Constants as K;
use NoreSources as ns;

/**
 * A list of parameter values to pass to the Connection::executeStatement() method
 * alongside a statement with parameters
 */
class StatementParameterArray implements \IteratorAggregate, \ArrayAccess, \Countable,
	ns\ArrayRepresentation
{

	const VALUE = 'value';

	const TYPE = 'type';

	public function getIterator()
	{
		return $this->table->getIterator();
	}

	/**
	 *
	 * @return integer Number of parameter values
	 */
	public function count()
	{
		return $this->table->count();
	}

	public function offsetExists($name)
	{
		return $this->table->offsetExists($name);
	}

	public function offsetGet($name)
	{
		return $this->table->offsetGet($name);
	}

	public function offsetSet($name, $value)
	{
		$this->set($name, $value);
	}

	public function offsetUnset($name)
	{
		$this->table->offsetUnset($name);
	}

	public function getArrayCopy()
	{
		return $this->table->getArrayCopy();
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
