<?php
namespace NoreSources\SQL;

use NoreSources as ns;
use Ferno\Loco as Loco;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

/**
 * Comma separated list of Expression
 */
class ListExpression extends \ArrayObject implements Expression
{

	public function __construct($array)
	{
		parent::__construct();
		if (ns\Container::isArray($array))
		{
			$this->exchangeArray($array);
		}
		else
		{
			$this->exchangeArray(ns\Container::createArray($array));
		}
	}

	public function tokenize(TokenStream &$stream, StatementContext $context)
	{
		$c = 0;
		foreach ($this as $value)
		{
			if ($c++ > 0)
				$stream->text(',')->space();

			$stream->expression($context->evaluateExpression($value), $context);
		}

		return $stream;
	}

	public function getExpressionDataType()
	{
		foreach ($this as $value)
		{
			return $value->getExpressionDataType();
		}

		return K::DATATYPE_UNDEFINED;
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
		foreach ($this as $value)
		{
			$value->traverse($callable, $context, $flags);
		}
	}
}