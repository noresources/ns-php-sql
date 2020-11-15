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
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\Statement;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\StructureElementIdentifier;

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
			$this->identifier($identifier);
	}

	/**
	 *
	 * @param string $identifier
	 *        	Namespace identifier
	 * @return \NoreSources\SQL\Syntax\Statement\Structure\DropViewQuery
	 */
	public function identifier($identifier)
	{
		if ($identifier instanceof NamespaceStructure)
			$identifier = $identifier->getPath();

		if ($identifier instanceof StructureElementIdentifier)
			$this->namespaceIdentifier = $identifier;
		else
			$this->namespaceIdentifier = new StructureElementIdentifier(
				\strval($identifier));

		return $this;
	}

	public function getNamespaceIdentifier()
	{
		return $this->namespaceIdentifier;
	}

	function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();
		$existsCondition = $platform->queryFeature(
			[
				K::PLATFORM_FEATURE_CREATE,
				K::PLATFORM_FEATURE_NAMESPACE,
				K::PLATFORM_FEATURE_EXISTS_CONDITION
			], false);

		$context->setStatementType(K::QUERY_CREATE_NAMESPACE);

		$stream->keyword('create')
			->space()
			->keyword(K::KEYWORD_NAMESPACE);
		if ($existsCondition)
			$stream->space()->keyword('if not exists');
		$stream->space()->identifier(
			$context->getPlatform()
				->quoteIdentifierPath($this->namespaceIdentifier));

		return $stream;
	}

	/**
	 * Namespace identifier
	 *
	 * @var StructureElementIdentifier
	 */
	private $namespaceIdentifier;
}
