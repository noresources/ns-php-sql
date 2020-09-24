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

/**
 * ATTACH DATABASE
 */
class SQLiteCreateNamespaceQuery extends CreateNamespaceQuery
{

	public function __construct($identifier = null)
	{
		parent::__construct($identifier);
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$builder = $context->getStatementBuilder();

		$path = $this->getNamespaceIdentifier() . '.sqlite';
		$structure = $context->findNamespace(
			$this->getNamespaceIdentifier()
				->getLocalName());
		if ($builder instanceof SQLiteStatementBuilder)
		{
			$provider = $builder->getSQLiteSetting(
				K::CONNECTION_DATABASE_FILE_PROVIDER);
			if (\is_callable($provider))
				$path = $provider($structure);
		}

		$path = new Literal($path, K::DATATYPE_STRING);

		return $stream->keyword('attach')
			->space()
			->keyword('database')
			->space()
			->expression($path, $context)
			->space()
			->keyword('as')
			->identifier(
			$builder->getPlatform()
				->quoteIdentifier(
				$this->getNamespaceIdentifier()
					->getLocalName()));
	}
}
