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
namespace NoreSources\SQL\Statement\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\StructureElementIdentifier;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\ViewStructure;

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
class DropViewQuery extends Statement
{

	public function __construct($identifier = null)
	{
		$this->viewIdentifier = null;
		if ($identifier != null)
			$this->identifier($identifier);
	}

	/**
	 *
	 * @param string|ViewStructure|StructureElementIdentifier $identifier
	 *        	View identifier
	 * @return \NoreSources\SQL\Statement\Structure\DropViewQuery
	 */
	public function identifier($identifier)
	{
		if ($identifier instanceof ViewStructure)
			$identifier = $identifier->getPath();

		if ($identifier instanceof StructureElementIdentifier)
			$this->viewIdentifier = $identifier;
		else
			$this->viewIdentifier = new StructureElementIdentifier(
				\strval($identifier));

		return $this;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();

		$hasExistsCondition = $platform->queryFeature(
			[
				K::PLATFORM_FEATURE_DROP,
				K::PLATFORM_FEATURE_VIEW,
				K::PLATFORM_FEATURE_EXISTS_CONDITION
			], false);
		$scopedStructure = $platform->queryFeature(
			[
				K::PLATFORM_FEATURE_VIEW,
				K::PLATFORM_FEATURE_SCOPED
			], false);

		$context->setStatementType(K::QUERY_DROP_VIEW);

		$stream->keyword('drop')
			->space()
			->keyword('view');
		if ($hasExistsCondition)
		{
			$stream->space()
				->keyword('if')
				->space()
				->keyword('exists');
		}

		$stream->space();

		if ($scopedStructure)
		{
			$parts = $this->viewIdentifier->getPathParts();
			if (\count($parts) > 1)
				$stream->identifier(
					$context->getPlatform()
						->quoteIdentifierPath($parts));
			else // Last chance to find the element namespace
			{
				$structure = $context->getPivot();
				if ($stream instanceof ViewStructure)
					$structure = $structure->getParentElement();

				if ($structure instanceof NamespaceStructure)
					$stream->identifier(
						$context->getPlatform()
							->quoteIdentifierPath($structure))
						->text('.');

				$stream->identifier(
					$context->getPlatform()
						->quoteIdentifier($this->viewIdentifier->path));
			}
		}
		else
			$stream->identifier(
				$context->getPlatform()
					->quoteIdentifier(
					$this->viewIdentifier->getLocalName()));

		return $stream;
	}

	/**
	 * View name
	 *
	 * @var StructureElementIdentifier
	 */
	private $viewIdentifier;
}
