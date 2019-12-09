<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression as X;
use NoreSources\Expression as xpr;
use NoreSources as ns;

class StatementParameterIterator implements \Iterator
{

	public function __construct(StatementParameterMap $map, $type)
	{
		$this->iterator = $map->getIterator();
		$this->keyType = $type;
	}

	public function current()
	{
		return $this->iterator->current();
	}

	public function key()
	{
		return $this->iterator->key();
	}

	public function next()
	{
		do
		{
			$this->iterator->next();
		}
		while ($this->iterator->valid() && ns\TypeDescription::getName($key) != $this->keyType);
	}

	public function valid()
	{
		return $this->iterator->valid();
	}

	public function rewind()
	{
		$this->iterator->rewind();
	}

	/**
	 *
	 * @var \Iterator
	 */
	private $iterator;

	private $keyType;
}

class StatementParameterMap extends \ArrayObject
{

	public function getNamedParameterCount()
	{
		return $this->namedParameterCount;
	}

	/**
	 *
	 * @return \NoreSources\SQL\StatementParameterIterator
	 */
	public function getNamedParameterIterator()
	{
		return (new StatementParameterIterator($this, 'string'));
	}

	/**
	 *
	 * @property-read integer $namedParameterCount
	 * @param string $member
	 * @throws \InvalidArgumentException
	 * @return Integer
	 */
	public function __get($member)
	{
		if ($member == 'namedParameterCount')
			return $this->namedParameterCount;

		throw new \InvalidArgumentException($member);
	}

	/**
	 * Number of parameter occurences
	 *
	 * @return integer Total number of parameter occurences
	 */
	public function count()
	{
		return (parent::count() - $this->namedParameterCount);
	}

	public function offsetSet($index, $newval)
	{
		if (\is_string($index))
		{
			if (!$this->offsetExists($index))
			{
				$this->namedParameterCount++;
			}
		}
		elseif (!\is_integer($index))
		{
			throw new \InvalidArgumentException('Invalid index. int or string expected.');
		}

		parent::offsetSet($index, $newval);
	}

	public function offsetUnset($index)
	{
		if (\is_string($index))
		{
			$this->namedParameterCount--;
		}

		parent::offsetUnset($index);
	}

	public function exchangeArray($input)
	{
		$this->namedParameterCount++;
		parent::exchangeArray($input);
		foreach ($this as $key => $value)
		{
			if (\is_string($key))
				$this->namedParameterCount++;
		}
	}

	private $namedParameterCount;
}

