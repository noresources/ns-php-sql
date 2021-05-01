<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
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
use NoreSources\SQL\Syntax\Statement\Structure\Traits\DropFlagsTrait;
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
class DropIndexQuery implements TokenizableStatementInterface
{

	use DropFlagsTrait;
	use IdenitifierTokenizationTrait;

	public function __construct($identifier = null)
	{
		$this->indexIdentifier = null;
		if ($identifier != null)
			$this->identifier($identifier);
	}

	public function getStatementType()
	{
		return K::QUERY_DROP_INDEX;
	}

	/**
	 *
	 * @param string|Identifier $identifier
	 *        	Index identifier
	 * @return \NoreSources\SQL\Syntax\Statement\Structure\DropIndexQuery
	 */
	public function identifier($identifier)
	{
		$this->indexIdentifier = Identifier::make($identifier);

		return $this;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();

		$platformDropFlags = $platform->queryFeature(
			[
				K::FEATURE_DROP,
				K::FEATURE_INDEX,
				K::FEATURE_DROP_FLAGS
			], 0);

		$stream->keyword('drop')
			->space()
			->keyword('index');
		if (($platformDropFlags & K::FEATURE_DROP_EXISTS_CONDITION))
		{
			$stream->space()
				->keyword('if')
				->space()
				->keyword('exists');
		}

		$stream->space();
		return $this->tokenizeIdentifier($stream, $context,
			$this->indexIdentifier, true);
	}

	/**
	 * Index name
	 *
	 * @var Identifier
	 */
	private $indexIdentifier;
}
