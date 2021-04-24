<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\Structure\DropIndexQuery;

class MySQLDropIndexQuery extends DropIndexQuery
{

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();
		return parent::tokenize($stream, $context)->space()
			->keyword('on')
			->space()
			->identifier(
			$platform->quoteIdentifierPath($context->getPivot()));
	}

	protected function tokenizeIdentifier(TokenStream $stream,
		TokenStreamContextInterface $context, Identifier $identifier,
		$qualified = true)
	{
		$platform = $context->getPlatform();
		return $stream->identifier(
			$platform->quoteIdentifier($identifier->getLocalName()));
	}
}
