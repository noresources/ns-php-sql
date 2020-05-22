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
use NoreSources\SQL\Structure\TableStructure;

/**
 * DROP TABLE statement
 */
class DropTableQuery extends Statement
{

	/**
	 *
	 * @param StructureElementIdentifier|TableStructure|string $identifier
	 *        	Table identifier
	 */
	public function __construct($identifier = null)
	{
		$this->tableIdentifier = null;
		if ($identifier != null)
			$this->identifier($identifier);
	}

	/**
	 *
	 * @param StructureElementIdentifier|TableStructure|string $identifier
	 *        	Table identifier
	 * @return \NoreSources\SQL\Statement\Structure\DropTableQuery
	 */
	public function identifier($identifier)
	{
		if ($identifier instanceof TableStructure)
			$identifier = $identifier->getPath();

		if ($identifier instanceof StructureElementIdentifier)
			$this->tableIdentifier = $identifier;
		else
			$this->tableIdentifier = new StructureElementIdentifier($identifier);

		return $this;
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		$builder = $context->getStatementBuilder();
		$builderFlags = $builder->getBuilderFlags(K::BUILDER_DOMAIN_GENERIC);
		$builderFlags |= $builder->getBuilderFlags(K::BUILDER_DOMAIN_DROP_TABLE);

		$context->setStatementType(K::QUERY_DROP_TABLE);

		$stream->keyword('drop')
			->space()
			->keyword('table');

		if ($builderFlags & K::BUILDER_IF_EXISTS)
		{
			$stream->space()
				->keyword('if')
				->space()
				->keyword('exists');
		}

		return $stream->space()->identifier(
			$builder->getCanonicalName($this->tableIdentifier->getPathParts()));
	}

	/**
	 *
	 * @var \NoreSources\SQL\Expression\StructureElementIdentifier
	 */
	private $tableIdentifier;
}
