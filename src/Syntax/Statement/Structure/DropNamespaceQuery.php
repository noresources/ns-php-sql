<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
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
use NoreSources\SQL\Syntax\Statement\Structure\Traits\DropFlagsTrait;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\ForIdentifierTrait;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\IdentifierPropertyTrait;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\IdentifierSelectionTrait;
use NoreSources\SQL\Syntax\Statement\Traits\IdenitifierTokenizationTrait;
use Psr\Log\LoggerInterface;

class DropNamespaceQuery implements TokenizableStatementInterface,
	StructureOperationQueryInterface
{
	use DropFlagsTrait;
	use IdenitifierTokenizationTrait;
	use IdentifierPropertyTrait;
	use ForIdentifierTrait;
	use IdentifierSelectionTrait;

	public function __construct($identifier = null)
	{
		if ($identifier)
			$this->identifier($identifier);
		$this->dropFlags(K::DROP_EXISTS_CONDITION);
	}

	public function getStatementType()
	{
		return K::QUERY_DROP_NAMESPACE;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();
		$platformDropFlags = $platform->queryFeature(
			[
				K::FEATURE_DROP,
				K::FEATURE_ELEMENT_NAMESPACE,
				K::FEATURE_DROP_FLAGS
			], 0);
		$cascade = ($this->getDropFlags() & K::DROP_CASCADE) &&
			($platformDropFlags & K::FEATURE_DROP_CASCADE);

		$stream->keyword('drop')
			->space()
			->keyword(K::KEYWORD_NAMESPACE)
			->space();

		if (($this->getDropFlags() & K::DROP_EXISTS_CONDITION) &&
			($platformDropFlags & K::DROP_EXISTS_CONDITION))
		{
			$stream->keyword('if')
				->space()
				->keyword('exists')
				->space();
		}

		$this->tokenizeNamespaceIdentifier($stream, $context);

		if ($this->getDropFlags() & K::DROP_CASCADE)
		{
			if ($cascade)
				$stream->space()->keyword('cascade');
			elseif ($context instanceof LoggerInterface)
				$context->notice('CASCADE option is not supported');
		}

		return $stream;
	}

	protected function tokenizeNamespaceIdentifier(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();
		$identifier = $this->selectIdentifier($context,
			NamespaceStructure::class, $this->getIdentifier(), false);

		return $stream->identifier(
			$platform->quoteIdentifier($identifier->getLocalName()));
	}
}
