<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources\ArrayUtil;
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

	function buildExpression(StatementContext $context)
	{
		$s = parent::buildExpression($builder, $resolver);
		if ($this->alias)
		{
			$s .= ' AS ' . $context->escapeIdentifier($this->alias);
		}

		return $s;
	}
}

abstract class Statement implements Expression
{
	public function getExpressionDataType()
	{
		return K::kDataTypeUndefined;
	}
}
