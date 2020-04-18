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

use NoreSources\Expression as xpr;

interface TokenizableExpressionInterface extends xpr\Expression
{

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\TokenizableExpressionInterface::tokenize()
	 * @return TokenStream
	 */
	function tokenize(TokenStream $stream, TokenStreamContextInterface $context);
}