<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources\SQL\Constants as K;

class StatementException extends \Exception
{

	public function __construct(Statement $statement, $message)
	{
		parent::__construct($message);
		$this->statement = $statement;
	}

	/**
	 *
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
	 *
	 * @var \Iterator
	 */
	public $iterator;
}

class StatementParameterMap extends \ArrayObject
{

	/**
	 *
	 * @return \NoreSources\SQL\NamedStatementParameterIterator
	 */
	public function getNamedParameterIterator()
	{
		return (new NamedStatementParameterIterator($this));
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

/**
 * Record column description
 */
class ResultColumn
{

	/**
	 *
	 * @var integer
	 */
	public $dataType;

	/**
	 *
	 * @var string
	 */
	public $name;

	/**
	 *
	 * @var TableColumnStructure
	 */
	public $column;

	/**
	 *
	 * @param integer|TableColumnStructure $data
	 */
	public function __construct($data)
	{
		$this->dataType = K::DATATYPE_UNDEFINED;
		$this->column = null;
		$this->name = null;
		if ($data instanceof TableColumnStructure)
		{
			$this->column = $data;
			$this->name = $data->getName();
			if ($data->hasProperty(K::COLUMN_PROPERTY_DATA_TYPE))
			{
				$this->dataType = $data->getProperty(K::COLUMN_PROPERTY_DATA_TYPE);
			}
		}
		elseif (\is_integer($data))
			$this->dataType = $data;
	}
}

/**
 */
class ResultColumnMap implements \Countable, \IteratorAggregate
{

	public function __construct()
	{
		$this->columns = new \ArrayObject();
	}

	public function __get($key)
	{
		return $this->getColumn($key);
	}

	public function getIterator()
	{
		return $this->columns->getIterator();
	}

	public function count()
	{
		return $this->columns->count();
	}

	public function getColumn($key)
	{
		if (!$this->columns->offsetExists($key))
		{
			foreach ($this->columns as $column)
			{
				if ($column->name == $key)
					return $column;
			}

			throw new \InvalidArgumentException($key);
		}

		return $this->columns->offsetGet($key);
	}

	public function setColumn($index, $data)
	{
		$d = new ResultColumn($data);
		$this->columns->offsetSet($index, $d);
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $columns;
}

trait StatementInputData
{

	public function getParameterCount()
	{
		return $this->parameters->count();
	}

	public function hasParameter($key)
	{
		return $this->parameters->offsetExists($key);
	}

	public function getParameter($key)
	{
		return $this->parameters->offsetGet($key);
	}

	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 *
	 * @param integer $position
	 *        	Parameter position in the statement
	 * @param string $key
	 *        	Logical parameter name
	 * @param string $dbmsName
	 *        	DMBS representation
	 */
	public function setParameter($position, $key, $dbmsName)
	{
		$this->parameters->offsetSet(intval($position), $dbmsName);
		$this->parameters->offsetSet(strval($key), $dbmsName);
	}

	/**
	 *
	 * @var StatementParameterMap Array of StatementParameter
	 *      The entry key is the parameter name as it appears in the Statement.
	 */
	private $parameters;
}

trait StatementOutputData
{

	public function getStatementType()
	{
		return $this->statementType;
	}

	/**
	 *
	 * @return number
	 */
	public function getResultColumnCount()
	{
		return $this->resultColumns->count();
	}

	public function getResultColumn($key)
	{
		return $this->resultColumns->getColumn($key);
	}

	public function getResultColumns()
	{
		return $this->resultColumns;
	}

	public function getResultColumnIterator()
	{
		return $this->resultColumns->getIterator();
	}

	/**
	 *
	 * @var integer
	 */
	private $statementType;

	/**
	 *
	 * @var ResultColumnMap
	 */
	private $resultColumns;
}

/**
 * SQL Table reference in a SQL query
 */
class TableReference extends TableExpression
{

	/**
	 *
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
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Expression::getExpressionDataType()
	 */
	public function getExpressionDataType()
	{
		return K::DATATYPE_UNDEFINED;
	}
}
