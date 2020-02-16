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

class Keyword implements Expression
{

	/**
	 * Keyword constant.
	 * One of Constants\KEYWORD_*.
	 *
	 * @var integer
	 */
	public $keyword;

	/**
	 *
	 * @param integer $keyword
	 */
	public function __construct($keyword)
	{
		$this->keyword = $keyword;
	}

	public function tokenize(TokenStream $stream, TokenStreamContext $context)
	{
		return $stream->keyword($context->getKeyword($this->keyword));
	}
}