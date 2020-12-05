<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\StructureElementIdentifier;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;

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
	 * @param string|StructureElementIdentifier $identifier
	 *        	Index identifier
	 * @return \NoreSources\SQL\Syntax\Statement\Structure\DropIndexQuery
	 */
	public function identifier($identifier)
	{
		$this->indexIdentifier = StructureElementIdentifier::make(
			$identifier);

		return $this;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();

		$scoped = $platform->queryFeature(
			[
				K::FEATURE_INDEX,
				K::FEATURE_SCOPED
			], false);
		$existsCondition = $platform->queryFeature(
			[
				K::FEATURE_DROP,
				K::FEATURE_INDEX,
				K::FEATURE_EXISTS_CONDITION
			], false);

		$stream->keyword('drop')
			->space()
			->keyword('index');
		if ($existsCondition)
		{
			$stream->space()
				->keyword('if')
				->space()
				->keyword('exists');
		}

		$stream->space();

		if ($scoped)
		{
			$parts = $this->indexIdentifier->getPathParts();
			if (\count($parts) > 1)
				$stream->identifier(
					$context->getPlatform()
						->quoteIdentifierPath($parts));
			else // Last chance to find the element namespace
			{
				$structure = $context->getPivot();

				if ($structure instanceof NamespaceStructure)
					$stream->identifier(
						$context->getPlatform()
							->quoteIdentifierPath($structure))
						->text('.');

				$stream->identifier(
					$context->getPlatform()
						->quoteIdentifier($this->indexIdentifier->path));
			}
		}
		else
			$stream->identifier(
				$context->getPlatform()
					->quoteIdentifier(
					$this->indexIdentifier->getLocalName()));

		return $stream;
	}

	/**
	 * Index name
	 *
	 * @var StructureElementIdentifier
	 */
	private $indexIdentifier;
}
