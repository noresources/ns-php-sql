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
 * A tokenizable object can be represented as a series of Token
 */
interface Tokenizable
{

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Tokenizable::tokenize()
	 * @return TokenStream
	 */
	function tokenize(TokenStream $stream, TokenStreamContext $context);
}
