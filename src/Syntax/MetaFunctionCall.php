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

/**
 * A special function which have different translation in DBMS dialects
 */
class MetaFunctionCall extends FunctionCall
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
			$context->getPlatform()
				->translateFunction($this), $stream, $context);
	}
}