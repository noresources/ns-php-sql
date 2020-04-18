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
 * Statement named parameter
 */
class Parameter implements TokenizableExpressionInterface
{

	/**
	 *
	 * @var string Parameter name
	 */
	public $name;

	/**
	 *
	 * @param string $name
	 *        	Parameter name
	 */
	public function __construct($name)
	{
		$this->name = $name;
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		return $stream->parameter($this->name);
	}
}