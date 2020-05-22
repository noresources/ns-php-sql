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
use NoreSources\SQL\Expression\StructureElementIdentifier;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Structure\NamespaceStructure;

/**
 * CREATE DATABASE / SCHEMA
 */
class CreateNamespaceQuery extends Statement
{

	/**
	 *
	 * @param string $identifier
	 *        	Namespace identifier
	 */
	public function __construct($identifier = null)
	{
		if ($identifier !== null)
			$this->name($identifier);
	}

	/**
	 *
	 * @param string $identifier
	 *        	Namespace identifier
	 * @return \NoreSources\SQL\Statement\Structure\DropViewQuery
	 */
	public function identifier($identifier)
	{
		if ($identifier instanceof NamespaceStructure)
			$identifier = $identifier->getPath();

		if ($identifier instanceof StructureElementIdentifier)
			$this->namespaceIdentifier = $identifier;
		else
			$this->namespaceIdentifier = new StructureElementIdentifier(\strval($identifier));

		return $this;
	}

	function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		$builder = $context->getStatementBuilder();

		$builderFlags = $builder->getBuilderFlags(K::BUILDER_DOMAIN_GENERIC);
		$builderFlags |= $builder->getBuilderFlags(K::BUILDER_DOMAIN_CREATE_NAMESPACE);

		$context->setStatementType(K::QUERY_CREATE_NAMESPACE);

		return $stream->keyword('create')
			->space()
			->keyword($builder->getKeyword(K::KEYWORD_NAMESPACE))
			->space()
			->identifier($builder->getCanonicalName($this->namespaceIdentifier));
	}

	/**
	 * Namespace identifier
	 *
	 * @var StructureElementIdentifier
	 */
	private $namespaceIdentifier;
}
