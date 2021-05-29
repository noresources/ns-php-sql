<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
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
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Syntax\Data;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\Structure\CreateNamespaceQuery;

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
		$identifier = $this->selectIdentifier($context,
			NamespaceStructure::class, $this->getIdentifier(), false);

		$factory = $platform->getStructureFilenameFactory();
		$structure = $context->findNamespace(
			$identifier->getLocalName());

		$path = $identifier . '.sqlite';
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
			$platform->quoteIdentifier($identifier->getLocalName()));
	}
}
