<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\Structure\DropNamespaceQuery;

class SQLiteDropNamespaceQuery extends DropNamespaceQuery
{

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();
		$stream->keyword('detach')
			->space()
			->keyword('database')
			->space();

		return $this->tokenizeNamespaceIdentifier($stream, $context);
	}
}
