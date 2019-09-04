<?php

namespace NoreSources\SQL;

use NoreSources as ns;
use Ferno\Loco as Loco;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

/**
 * /**
 * Option of a CASE expression
 */
class CaseOptionExpression
{

	/**
	 * @var Expression
	 */
	public $when;

	/**
	 * @var Expression
	 */
	public $then;

	public function __construct(Expression $when, Expression $then)
	{
		$this->when = $when;
		$this->then = $then;
	}

	function buildExpression(StatementContext $context)
	{
		return 'WHEN ' . $this->when->buildExpression($context) . ' THEN ' . $this->then->buildExpression($context);
	}

	function getExpressionDataType()
	{
		return $this->then->getExpressionDataType();
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
		$this->when->traverse($callable, $context, $flags);
		$this->then->traverse($callable, $context, $flags);
	}
}

/**
 * CASE
 */
class CaseExpression implements Expression
{

	/**
	 * @var Expression
	 */
	public $subject;

	/**
	 * @var \ArrayObject
	 */
	public $options;

	/**
	 * @var Expression
	 */
	public $else;

	public function __construct(Expression $subject)
	{
		$this->subject = $subject;
		$this->options = new \ArrayObject();
		$this->else = null;
	}

	public function buildExpression(StatementContext $context)
	{
		$s = 'CASE ' . $this->subject;
		foreach ($this->options as $option)
		{
			$s .= ' ' . $option->buildExpression($context);
		}

		if ($this->else instanceof Expression)
		{
			$s .= ' ELSE ' . $this->else->buildExpression($context);
		}

		return $s;
	}

	public function getExpressionDataType()
	{
		$set = false;
		$current = K::DATATYPE_UNDEFINED;

		foreach ($this->options as $option)
		{
			$t = $option->getExpressionDataType();
			if ($set && ($t != $current))
			{
				return K::DATATYPE_UNDEFINED;
			}

			$set = true;
			$current = $t;
		}

		if ($this->else instanceof Expression)
		{
			$t = $this->else->getExpressionDataType();
			if ($set && ($t != $current))
			{
				return K::DATATYPE_UNDEFINED;
			}

			$current = $t;
		}

		return $current;
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
		$this->subject->traverse($callable, $context, $flags);
		foreach ($this->options as $option)
		{
			$option->traverse($callable, $context, $flags);
		}
		$this->else->traverse($callable, $context, $flags);
	}
}
