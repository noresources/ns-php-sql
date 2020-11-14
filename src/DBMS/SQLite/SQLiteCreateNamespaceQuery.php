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

use NoreSources\Container;
use NoreSources\SQL\DBMS\SQLite\SQLiteConstants as K;
use NoreSources\SQL\Expression\Data;
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
		$platform = $context->getPlatform();

		$factory = $platform->getStructureFilenameFactory();
		$structure = $context->findNamespace(
			$this->getNamespaceIdentifier()
				->getLocalName());

		$path = $this->getNamespaceIdentifier() . '.sqlite';
		if (!\is_callable($factory) &&
			(Container::isTraversable($factory) ||
			Container::isArray($factory)))
			$factory = Container::createArray($factory);

		if (\is_callable($factory))
			$path = $factory($structure);

		$path = new Data($path, K::DATATYPE_STRING);

		return $stream->keyword('attach')
			->space()
			->keyword('database')
			->space()
			->expression($path, $context)
			->space()
			->keyword('as')
			->identifier(
			$platform->quoteIdentifier(
				$this->getNamespaceIdentifier()
					->getLocalName()));
	}
}
