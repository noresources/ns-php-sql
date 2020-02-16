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

class Parameter implements Expression
{

	public $name;

	public function __construct($name)
	{
		$this->name = $name;
	}

	public function tokenize(TokenStream $stream, TokenStreamContext $context)
	{
		return $stream->parameter($this->name);
	}
}