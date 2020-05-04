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
namespace NoreSources\SQL\Statement\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Statement\StatementException;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\StructureAwareInterface;
use NoreSources\SQL\Structure\StructureAwareTrait;

/**
 * CREATE DATABASE / SCHEMA
 */
class CreateNamespaceQuery extends Statement implements StructureAwareInterface
{

	use StructureAwareTrait;

	public function __construct(NamespaceStructure $structure = null)
	{
		$this->structure = $structure;
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		$builder = $context->getStatementBuilder();

		$builderFlags = $builder->getBuilderFlags(K::BUILDER_DOMAIN_GENERIC);
		$builderFlags |= $builder->getBuilderFlags(K::BUILDER_DOMAIN_CREATE_NAMESPACE);

		$structure = $this->getStructure();

		if (!($structure instanceof NamespaceStructure && ($structure->count() > 0)))
			throw new StatementException($this, 'Missing or invalid table structure');

		return $stream->keyword('create')
			->space()
			->keyword($builder->getKeyword(K::KEYWORD_NAMESPACE))
			->space()
			->identifier($builder->escapeIdentifier($structure->getName()));
	}
}
