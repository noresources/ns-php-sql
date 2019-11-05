<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources\SQL\Constants as K;
use NoreSources as ns;

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

	/**
	 *
	 * @param integer|string $key
	 * @throws \InvalidArgumentException
	 * @return ResultColumn
	 */
	public function getColumn($key)
	{
		if (!$this->columns->offsetExists($key))
		{
			foreach ($this->columns as $column)
			{
				if ($column->name == $key)
					return $column;
			}

			throw new \InvalidArgumentException(
				ns\TypeDescription::getName($key) . ' ' . $key . ' is not a valid result column key');
		}

		return $this->columns->offsetGet($key);
	}

	public function setColumn($index, $data)
	{
		if (!($data instanceof ResultColumn))
			$data = new ResultColumn($data);
		$this->columns->offsetSet($index, $data);
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $columns;
}

interface StatementInputData
{

	/**
	 *
	 * @return integer
	 */
	function getNamedParameterCount();

	/**
	 *
	 * @return integer Total number of parameter occurences
	 */
	function getParameterCount();

	/**
	 *
	 * @param integer|string $key
	 *        	Parameter position or name
	 * @return boolean
	 */
	function hasParameter($key);

	/**
	 *
	 * @param integer|string $key
	 *        	Parameter name or index
	 * @return string DBMS representation of the parameter name
	 */
	function getParameter($key);

	/**
	 *
	 * @return StatementParameterMap
	 */
	function getParameters();

	/**
	 *
	 * @param integer $position
	 *        	Parameter position in the statement
	 * @param string $key
	 *        	Parameter name
	 * @param string $dbmsName
	 *        	DBMS representation of the parameter name
	 */
	function registerParameter($position, $key, $dbmsName);

	function initializeStatementInputData(StatementInputData $data = null);
}

/**
 * Implementation of StatementInputData
 */
trait StatementInputDataTrait
{

	public function getNamedParameterCount()
	{
		return $this->parameters->getNamedParameterCount();
	}

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

	public function registerParameter($position, $key, $dbmsName)
	{
		$this->parameters->offsetSet(intval($position), $dbmsName);
		$this->parameters->offsetSet(strval($key), $dbmsName);
	}

	public function initializeStatementInputData(StatementInputData $data = null)
	{
		if ($data)
			$this->parameters = $data->getParameters();
		else
			$this->parameters = new StatementParameterMap();
	}

	/**
	 *
	 * @var StatementParameterMap Array of StatementParameter
	 *      The entry key is the parameter name as it appears in the Statement.
	 */
	private $parameters;
}

interface StatementOutputData
{

	/**
	 *
	 * @return integer
	 */
	function getStatementType();

	/**
	 *
	 * @return integer
	 */
	function getResultColumnCount();

	/**
	 *
	 * @param string $key
	 * @return ResultColumn
	 */
	function getResultColumn($key);

	/**
	 *
	 * @return ResultColumnMap
	 */
	function getResultColumns();

	/**
	 *
	 * @return \ArrayIterator
	 */
	function getResultColumnIterator();

	/**
	 *
	 * @param StatementOutputData $data
	 */
	function initializeStatementOutputData($data = null);
}

/**
 * Implementation of StatementOutputData
 */
trait StatementOutputDataTrait
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

	public function initializeStatementOutputData($data = null)
	{
		if ($data instanceof StatementOutputData)
		{
			$this->statementType = $data->getStatementType();
			$this->resultColumns = $data->getResultColumns();
		}
		else
		{
			$this->statementType = Statement::statementTypeFromData($data);
			$this->resultColumns = new ResultColumnMap();
		}
	}

	/**
	 *
	 * @var integer
	 */
	protected $statementType;

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

	public static function statementTypeFromData($data)
	{
		if (\is_object($data))
		{
			if ($data instanceof SelectQuery)
				return K::QUERY_SELECT;
			elseif ($data instanceof InsertionQuery)
				return K::QUERY_INSERT;
			elseif ($data instanceof UpdateQuery)
				return K::QUERY_UPDATE;
			elseif ($data instanceof DeleteQuery)
				return K::QUERY_DELETE;

			$type = 0;
			if ($type instanceof StatementOutputData)
				$type = $data->getExpressionDataType();

			if ($type != 0)
				return $type;

			if ($data instanceof PreparedStatement)
				$data = $data->getStatement();
			elseif ($data instanceof StatementContext)
				$data = strval($data);
		}

		if (\is_string($data))
		{
			$regex = [
				'/^select\s+/i' => K::QUERY_SELECT,
				'/^insert\s+/i' => K::QUERY_INSERT,
				'/^update\s+/i' => K::QUERY_UPDATE,
				'/^delete\s+/i' => K::QUERY_DELETE,
				'/^create\s+/i' => K::QUERY_FAMILY_CREATE
			];

			foreach ($regex as $r => $t)
			{
				if (preg_match($r, $data))
					return $t;
			}
		}

		return 0;
	}
}

/**
 * Implements \ArrayAccess
 */
trait ColumnValueTrait
{

	/**
	 * Set a column value with an evaluable value
	 *
	 * @param
	 *        	string Column name
	 * @param
	 *        	Evaluable Evaluable expression
	 *        	
	 * @throws \BadMethodCallException
	 * @throws \InvalidArgumentException
	 */
	public function __invoke()
	{
		$args = func_get_args();
		if (count($args) != 2)
			throw new \BadMethodCallException(__CLASS__ . ' invokation expects exactly 2 arguments');

		if (!\is_string($args[0]))
			throw new \InvalidArgumentException(__CLASS__ . '() first argument expects string');

		$this->set($args[0], $args[1], true);
	}

	/**
	 *
	 * @param string $columnName
	 * @param mixed $columnValue
	 * @param boolean $evaluate
	 *        	If @c true, the value will be evaluated at build stage. Otherwise, the value is considered as a
	 *        	literal of the same type as the column data type..
	 *        	If @c null, the
	 * @return \NoreSources\SQL\UpdateQuery
	 */
	public function set($columnName, $columnValue, $evaluate = null)
	{
		if ($evaluate === null)
			$evaluate = ($columnValue instanceof Evaluable) || (ns\Container::isArray($columnValue));

		if ($evaluate)
			$columnValue = ExpressionEvaluator::evaluate($columnValue);

		$this->columnValues->offsetSet($columnName, $columnValue);
		return $this;
	}

	/**
	 *
	 * @param
	 *        	string Column name
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return $this->columnValues->offsetExists($offset);
	}

	/**
	 * Get current column value
	 *
	 * @param
	 *        	string Column name
	 *        	
	 * @return mixed Column current value or @c null if not set
	 */
	public function offsetGet($offset)
	{
		if ($this->columnValues->offsetExists($index))
			return $this->columnValues[$offset]['value'];
		return null;
	}

	/**
	 *
	 * @param string $offset
	 *        	Column name
	 * @param mixed $value
	 *        	Column value.
	 */
	public function offsetSet($offset, $value)
	{
		$evaluate = false;
		if ($value instanceof Evaluable)
			$evaluate = true;

		$this->set($offset, $value, $evaluate);
	}

	public function offsetUnset($offset)
	{
		$this->columnValues->offsetUnset($offset);
	}

	protected function traverseColumnValues($callable, StatementContext $context, $flags = 0)
	{
		foreach ($this->columnValues as $column => $value)
		{
			if ($value instanceof Expression)
				$value->traverse($callable, $context, $flags);
		}
	}

	/**
	 *
	 * @var \ArrayObject Associative array where
	 *      keys are column names
	 *      and values are \NoreSources\SQL\Expression
	 */
	private $columnValues;
}
