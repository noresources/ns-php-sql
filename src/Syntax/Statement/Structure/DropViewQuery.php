<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\ViewStructure;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\DropFlagsTrait;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\ForIdentifierTrait;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\IdentifierPropertyTrait;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\IdentifierSelectionTrait;

/**
 * DROP VIEW statement
 *
 * References
 * <dl>
 * <dt>SQLite</dt>
 * <dd></dd>
 * <dt>MySQL</dt>
 * <dd></dd>
 * <dt>PostgreSQL</dt>
 * <dd></dd>
 * </dl>
 */
class DropViewQuery implements TokenizableStatementInterface,
	StructureOperationQueryInterface
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
		return K::QUERY_DROP_VIEW;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$identifier = $this->selectIdentifier($context,
			ViewStructure::class, $this->getIdentifier(), true);

		$platform = $context->getPlatform();

		$platformDropFlags = $platform->queryFeature(
			[
				K::FEATURE_DROP,
				K::FEATURE_ELEMENT_VIEW,
				K::FEATURE_DROP_FLAGS
			], 0);

		$stream->keyword('drop')
			->space()
			->keyword('view');
		if (($this->getDropFlags() & K::DROP_EXISTS_CONDITION) &&
			($platformDropFlags & K::FEATURE_DROP_EXISTS_CONDITION))
		{
			$stream->space()
				->keyword('if')
				->space()
				->keyword('exists');
		}

		return $stream->space()->identifier(
			$platform->quoteIdentifierPath($identifier));
	}
}
