<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

use NoreSources\Expression\ExpressionInterface;

interface TokenizableExpressionInterface extends ExpressionInterface
{

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\TokenizableExpressionInterface::tokenize()
	 * @return TokenStream
	 */
	function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context);
}