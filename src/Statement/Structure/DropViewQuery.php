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
			$this->viewIdentifier = new StructureElementIdentifier(\strval($identifier));

		return $this;
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		$builder = $context->getStatementBuilder();
		$builderFlags = $builder->getBuilderFlags(K::BUILDER_DOMAIN_GENERIC);
		$builderFlags |= $builder->getBuilderFlags(K::BUILDER_DOMAIN_DROP_VIEW);

		$context->setStatementType(K::QUERY_DROP_VIEW);

		$stream->keyword('drop')
			->space()
			->keyword('view');
		if ($builderFlags & K::BUILDER_IF_EXISTS)
		{
			$stream->space()
				->keyword('if')
				->space()
				->keyword('exists');
		}

		$stream->space();

		if ($builderFlags & K::BUILDER_SCOPED_STRUCTURE_DECLARATION)
		{
			$parts = $this->viewIdentifier->getPathParts();
			if (\count($parts) > 1)
				$stream->identifier($builder->getCanonicalName($parts));
			else // Last chance to find the element namespace
			{
				$structure = $context->getPivot();
				if ($stream instanceof ViewStructure)
					$structure = $structure->getParentElement();

				if ($structure instanceof NamespaceStructure)
					$stream->identifier($builder->getCanonicalName($structure))
						->text('.');

				$stream->identifier($builder->escapeIdentifier($this->viewIdentifier->path));
			}
		}
		else
			$stream->identifier($builder->escapeIdentifier($this->viewIdentifier->getLocalName()));

		return $stream;
	}

	/**
	 * View name
	 *
	 * @var StructureElementIdentifier
	 */
	private $viewIdentifier;
}
