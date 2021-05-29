<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\IndexStructure;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\DropFlagsTrait;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\ForIdentifierTrait;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\IdentifierPropertyTrait;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\IdentifierSelectionTrait;
use NoreSources\SQL\Syntax\Statement\Traits\IdenitifierTokenizationTrait;

/**
 * DROP INDEX statement
 *
 * References
 * <dl>
 * <dt>SQLite</dt>
 * <dd>https://www.sqlite.org/lang_createindex.html</dd>
 * <dt>MySQL</dt>
 * <dd></dd>
 * <dt>PostgreSQL</dt>
 * <dd></dd>
 * </dl>
 */
class DropIndexQuery implements TokenizableStatementInterface,
	StructureOperationQueryInterface
{

	use DropFlagsTrait;
	use IdenitifierTokenizationTrait;
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
		return K::QUERY_DROP_INDEX;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$identifier = $this->selectIdentifier($context,
			IndexStructure::class, $this->getIdentifier(), true);

		$platform = $context->getPlatform();

		$platformDropFlags = $platform->queryFeature(
			[
				K::FEATURE_DROP,
				K::FEATURE_ELEMENT_INDEX,
				K::FEATURE_DROP_FLAGS
			], 0);

		$stream->keyword('drop')
			->space()
			->keyword('index');
		if (($this->getDropFlags() & K::DROP_EXISTS_CONDITION) &&
			($platformDropFlags & K::FEATURE_DROP_EXISTS_CONDITION))
		{
			$stream->space()
				->keyword('if')
				->space()
				->keyword('exists');
		}

		$stream->space();
		return $this->tokenizeIdentifier($stream, $context, $identifier,
			true);
	}

	protected function tokenizeIdentifier(TokenStream $stream,
		TokenStreamContextInterface $context, Identifier $identifier,
		$qualified = true)
	{
		$parts = $this->getIdentifier()->getPathParts();
		if (Container::count($parts) == 3)
		{
			$ns = \array_shift($parts);
			$name = \array_pop($parts);
			$identifier = Identifier::make([
				$ns,
				$name
			]);
		}

		$platform = $context->getPlatform();
		return $stream->identifier(
			$platform->quoteIdentifierPath($identifier));
	}
}
