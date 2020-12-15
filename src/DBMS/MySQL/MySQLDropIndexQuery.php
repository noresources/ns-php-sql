<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\SQL\Structure\StructureElementIdentifier;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\Structure\DropIndexQuery;

class MySQLDropIndexQuery extends DropIndexQuery
{

	protected function tokenizeIdentifier(TokenStream $stream,
		TokenStreamContextInterface $context,
		StructureElementIdentifier $identifier)
	{
		$platform = $context->getPlatform();
		return parent::tokenizeIdentifier($stream, $context, $identifier)->space()
			->keyword('on')
			->space()
			->identifier(
			$platform->quoteIdentifierPath($context->getPivot()));
	}
}
