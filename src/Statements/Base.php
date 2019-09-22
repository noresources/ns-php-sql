<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources\Creole\PreformattedBlock;
use NoreSources\SQL\Constants as K;

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

class StatementParameterMap extends \ArrayObject
{

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
