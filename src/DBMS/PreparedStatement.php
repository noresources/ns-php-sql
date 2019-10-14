<?php
namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\SQL\Constants as K;

/**
 * A list of parameter values to pass to the Connection::executeStatement() method
 * alongside a statement with parameters
 */
class ParameterArray implements \IteratorAggregate, \Countable
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

/**
 * Pre-built statement
 */
abstract class PreparedStatement
{

	use StatementInputData;
	use StatementOutputData;

	/**
	 *
	 * @param
	 *        	string|StatementContext Statement data
	 */
	public function __construct($data)
	{
		if ($data instanceof StatementContext)
		{
			$this->statementType = $data->getStatementType();
			$this->resultColumns = $data->getResultColumns();
			$this->parameters = $data->getParameters();
		}
		else
		{
			$this->parameters = new StatementParameterMap();
			$this->statementType = 0;
			$this->resultColumns = new ResultColumnMap();
		}
	}

	/**
	 *
	 * @retur string SQL statement string
	 */
	public function __toString()
	{
		return $this->getStatement();
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
	 * @var StatementParameterMap
	 */
	private $parameters;
}