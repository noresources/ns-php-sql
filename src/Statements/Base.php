<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources\Creole\PreformattedBlock;
use NoreSources\SQL\Constants as K;
use phpDocumentor\Reflection\Types\Integer;

class StatementException extends \Exception
{

	public function __construct(Statement $statement, $message)
	{
		parent::__construct($message);
		$this->statement = $statement;
	}

	/**
	 * @return \NoreSources\SQL\Statement
	 */
	public function getStatement()
	{
		return $this->statement;
	}

	private $statement;
}

class NamedStatementParameterIterator implements \Iterator
{

	public function __construct(StatementParameterMap $map)
	{
		$this->iterator = $map->getIterator();
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
		while ($this->iterator->valid() && \is_integer($this->iterator->key()));
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
	 * @var \Iterator
	 */
	public $iterator;
}

class StatementParameterMap extends \ArrayObject
{

	/**
	 * @return \NoreSources\SQL\NamedStatementParameterIterator
	 */
	public function getNamedParameterIterator()
	{
		return (new NamedStatementParameterIterator($this));
	}
	
	/**
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

class StatementData
{

	/**
	 * @var string
	 */
	public $sql;

	/**
	 * @var StatementParameterMap Array of StatementParameter
	 *      The entry key is the parameter name as it appears in the Statement.
	 */
	public $parameters;

	public function __construct()
	{
		$this->sql = '';
		$this->parameters = new StatementParameterMap();
	}

	public function __toString()
	{
		return $this->sql;
	}
}

/**
 * SQL Table reference in a SQL query
 */
class TableReference extends TableExpression
{

	/**
	 * @var string
	 */
	public $alias;

	public function __construct($path, $alias = null)
	{
		parent::__construct($path);
		$this->alias = $alias;
	}
}

abstract class Statement implements Expression
{
	/**
	 * Most of statement does not provide return values
	 * {@inheritDoc}
	 * @see \NoreSources\SQL\Expression::getExpressionDataType()
	 */
	public function getExpressionDataType()
	{
		return K::DATATYPE_UNDEFINED;
	}
}
