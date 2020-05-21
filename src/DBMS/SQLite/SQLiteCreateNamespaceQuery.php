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

use NoreSources\SQL\DBMS\SQLite\SQLiteConstants as K;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Statement\Structure\CreateNamespaceQuery;
use NoreSources\SQL\Structure\NamespaceStructure;

/**
 * ATTACH DATABASE
 */
class SQLiteCreateNamespaceQuery extends CreateNamespaceQuery
{

	public function __construct(NamespaceStructure $structure = null)
	{}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		$builder = $context->getStatementBuilder();

		$path = $this->getStructure()->getName() . '.sqlite';
		if ($builder instanceof SQLiteStatementBuilder)
		{
			$directory = $builder->getSQLiteSettings(K::CONNECTION_DATABASE_FILE_DIRECTORY);
			if (\is_string($directory))
				$path = $directory . '/' . $path;
		}

		$path = new Literal($path, K::DATATYPE_STRING);

		return $stream->keyword('attach')
			->space()
			->keyword('database')
			->space()
			->expression($path, $context)
			->space()
			->keyword('as')
			->identifier($builder->escapeIdentifier($this->getStructure()
			->getName()));
	}
}
