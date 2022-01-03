<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\CreateFlagsTrait;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\ForIdentifierTrait;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\IdentifierPropertyTrait;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\IdentifierSelectionTrait;

/**
 * CREATE DATABASE / SCHEMA
 */
class CreateNamespaceQuery implements TokenizableStatementInterface,
	StructureOperationQueryInterface
{
	use CreateFlagsTrait;
	use IdentifierPropertyTrait;
	use ForIdentifierTrait;
	use IdentifierSelectionTrait;

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

	function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();

		$identifier = $this->selectIdentifier($context,
			NamespaceStructure::class, $this->getIdentifier(), false);

		$platformCreateFlags = $platform->queryFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_ELEMENT_NAMESPACE,
				K::FEATURE_CREATE_EXISTS_CONDITION
			], 0);

		$stream->keyword('create')
			->space()
			->keyword(K::KEYWORD_NAMESPACE);
		if (($this->getCreateFlags() & K::CREATE_EXISTS_CONDITION) &&
			($platformCreateFlags & K::FEATURE_CREATE_EXISTS_CONDITION))
			$stream->space()->keyword('if not exists');
		$stream->space()->identifier(
			$context->getPlatform()
				->quoteIdentifierPath($identifier));

		return $stream;
	}
}
