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
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Statement\Traits\StatementTableTrait;
use Psr\Log\LoggerInterface;

/**
 * DROP TABLE statement
 */
class DropTableQuery extends Statement
{
	use StatementTableTrait;

	const CASCADE = 0x01;

	/**
	 *
	 * @param StructureElementIdentifier|TableStructure|string $identifier
	 *        	Table identifier
	 */
	public function __construct($identifier = null)
	{
		$this->dropFlags = 0;
		if ($identifier != null)
			$this->table($identifier);
	}

	/**
	 * Set DROP TABLE option flags
	 *
	 * @param integer $flags
	 * @return \NoreSources\SQL\Statement\Structure\DropTableQuery
	 */
	public function flags($flags)
	{
		$this->dropFlags = $flags;
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

		$stream->space()->identifier($builder->getCanonicalName($this->getTable()
			->getPathParts()));

		if ($this->dropFlags & self::CASCADE)
		{
			if ($builderFlags & K::BUILDER_DROP_CASCADE)
				$stream->space()->keyword('cascade');
			elseif ($builder instanceof LoggerInterface)
				$builder->notice('CASCADE option is not supported');
		}

		return $stream;
	}

	/**
	 * DROP TABLE options
	 *
	 * @var integer
	 */
	private $dropFlags;
}
