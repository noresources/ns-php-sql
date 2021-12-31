<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\PlatformInterface;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\ViewStructure;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\DropFlagsTrait;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\ForIdentifierTrait;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\IdentifierPropertyTrait;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\IdentifierSelectionTrait;
use Psr\Log\LoggerInterface;

abstract class DropStructureElementQuery implements
	TokenizableStatementInterface, StructureOperationQueryInterface
{

	use DropFlagsTrait;
	use IdentifierPropertyTrait;
	use ForIdentifierTrait;
	use IdentifierSelectionTrait;

	public function __construct($identifier = null)
	{
		if ($identifier != null)
			$this->identifier($identifier);
		$this->dropFlags(K::DROP_EXISTS_CONDITION);
	}

	public function getStatementType()
	{
		return K::QUERY_FAMILY_DROP;
	}

	/**
	 *
	 * @param PlatformInterface $platform
	 *        	If provided. The method may return
	 *        	the DBMS-specific structure name instead of the generic one. Should be useful for
	 *        	namespaces.
	 * @return string The structure name as it should appear in query
	 */
	abstract function getStructureTypeName(
		PlatformInterface $platform = null);

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$identifier = $this->selectIdentifier($context,
			ViewStructure::class, $this->getIdentifier(), true);

		$platform = $context->getPlatform();

		$platformDropFlags = $platform->queryFeature(
			[
				K::FEATURE_DROP,
				$this->getStructureTypeName(),
				K::FEATURE_DROP_FLAGS
			], 0);

		$cascade = ($this->getDropFlags() & K::DROP_CASCADE) &&
			($platformDropFlags & K::FEATURE_DROP_CASCADE);

		$stream->keyword('drop')
			->space()
			->keyword($this->getStructureTypeName($platform));
		if (($this->getDropFlags() & K::DROP_EXISTS_CONDITION) &&
			($platformDropFlags & K::FEATURE_DROP_EXISTS_CONDITION))
		{
			$stream->space()
				->keyword('if')
				->space()
				->keyword('exists');
		}

		$stream->space();
		$this->tokenizeIdentifier($stream, $context, $identifier);

		if ($this->getDropFlags() & K::DROP_CASCADE)
		{
			if ($cascade)
				$stream->space()->keyword('cascade');
			elseif ($context instanceof LoggerInterface)
				$context->notice('CASCADE option is not supported');
		}

		return $stream;
	}

	protected function tokenizeIdentifier(TokenStream $stream,
		TokenStreamContextInterface $context, Identifier $identifier,
		$qualified = true)
	{
		$platform = $context->getPlatform();
		return $stream->identifier(
			$platform->quoteIdentifierPath($identifier));
	}
}
