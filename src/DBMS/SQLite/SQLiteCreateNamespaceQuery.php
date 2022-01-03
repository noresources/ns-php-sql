<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\SQL\DBMS\Filesystem\StructureFilenameFactoryInterface;
use NoreSources\SQL\DBMS\Filesystem\StructureFilenameFactoryProviderInterface;
use NoreSources\SQL\DBMS\SQLite\SQLiteConstants as K;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Syntax\Data;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\Structure\CreateNamespaceQuery;
use NoreSources\Type\TypeDescription;

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

		if ($platform instanceof StructureFilenameFactoryProviderInterface)
		{
			$factory = $platform->getStructureFilenameFactory();
			if ($factory instanceof StructureFilenameFactoryInterface)
				$path = $factory->buildStructureFilename(
					$structure->getIdentifier(),
					TypeDescription::getName($structure));
		}

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
