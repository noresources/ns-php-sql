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
		return $context->getStatementBuilder()
			->getPlatform()
			->translateFunction($this)
			->tokenize($stream, $context);
	}
}