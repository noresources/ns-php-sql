<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

use NoreSources\Expression\ProcedureInvocation;

/**
 * SQL function call expression
 *
 * @deprecated Use ProcedureInvocation directly
 */
class FunctionCall extends ProcedureInvocation implements
	TokenizableExpressionInterface
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

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		return Tokenizer::getInstance()->tokenizeProcedureInvocation(
			$this, $stream, $context);
	}
}