<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Expression;

use NoreSources\Expression\ProcedureInvocation;

/**
 * SQL function call expression
 */
class FunctionCall extends ProcedureInvocation implements TokenizableExpressionInterface
{

	/**
	 *
	 * @param string $name
	 * @param array $arguments
	 */
	public function __construct($name, $arguments = array())
	{
		parent::__construct($name, $arguments);
	}

	/**
	 *
	 * @param mixed $argument
	 * @return FunctionCall
	 */
	public function appendArgument($argument)
	{
		if (!($argument instanceof TokenizableExpressionInterface))
		{
			$argument = Evaluator::evaluate($argument);
		}

		return parent::appendArgument($argument);
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		$stream->keyword($this->getFunctionName())
			->text('(');
		$index = 0;
		foreach ($this as $a)
		{
			if ($index++ > 0)
				$stream->text(', ');

			$stream->expression($a, $context);
		}
		return $stream->text(')');
	}
}