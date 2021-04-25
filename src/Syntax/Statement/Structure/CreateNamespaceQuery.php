<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\CreateFlagsTrait;

/**
 * CREATE DATABASE / SCHEMA
 */
class CreateNamespaceQuery implements TokenizableStatementInterface
{
	use CreateFlagsTrait;

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

	public function getStatementType()
	{
		return K::QUERY_CREATE_NAMESPACE;
	}

	/**
	 *
	 * @param string $identifier
	 *        	Namespace identifier
	 * @return \NoreSources\SQL\Syntax\Statement\Structure\DropViewQuery
	 */
	public function identifier($identifier)
	{
		$this->namespaceIdentifier = Identifier::make($identifier);
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
		$platformCreateFlags = $platform->queryFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_NAMESPACE,
				K::FEATURE_CREATE_EXISTS_CONDITION
			], 0);

		$stream->keyword('create')
			->space()
			->keyword(K::KEYWORD_NAMESPACE);
		if (($platformCreateFlags & K::FEATURE_CREATE_EXISTS_CONDITION))
			$stream->space()->keyword('if not exists');
		$stream->space()->identifier(
			$context->getPlatform()
				->quoteIdentifierPath($this->namespaceIdentifier));

		return $stream;
	}

	/**
	 * Namespace identifier
	 *
	 * @var Identifier
	 */
	private $namespaceIdentifier;
}
