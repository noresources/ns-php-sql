<?php
/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Expression;

use NoreSources\Expression as xpr;
use NoreSources\SQL\Statement\BuildContext;

class FunctionCall extends xpr\Procedure implements Expression
{

	public function __construct($name, $arguments = array())
	{
		parent::__construct($name, $arguments);
	}

	public function appendArgument($argument)
	{
		if (!($argument instanceof Expression))
		{
			$argument = Evaluator::evaluate($argument);
		}
		return parent::appendArgument($argument);
	}

	public function tokenize(TokenStream $stream, BuildContext $context)
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