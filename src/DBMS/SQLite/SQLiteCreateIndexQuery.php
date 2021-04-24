<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

//
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\Structure\CreateIndexQuery;

class SQLiteCreateIndexQuery extends CreateIndexQuery
{

	public function tokenizeIndexIdentifier(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		return $this->tokenizeIdentifier($stream, $context,
			$this->getIdentifier(), true);
	}

	public function tokznizeIndexTable(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();
		return $stream->identifier(
			$platform->quoteIdentifier(
				$this->getTable()
					->getLocalName()));
	}
}
