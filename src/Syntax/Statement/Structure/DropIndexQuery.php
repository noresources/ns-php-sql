<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\Container\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\PlatformInterface;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\IndexStructure;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;

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
class DropIndexQuery extends DropStructureElementQuery
{

	public function getStructureTypeName(
		PlatformInterface $platform = null)
	{
		return K::STRUCTURE_INDEX;
	}

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
				$this->getStructureTypeName(),
				K::FEATURE_DROP_FLAGS
			], 0);

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
