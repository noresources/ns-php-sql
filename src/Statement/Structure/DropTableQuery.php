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
use NoreSources\SQL\Structure\StructureElementIdentifier;
use NoreSources\SQL\Structure\TableStructure;
use Psr\Log\LoggerInterface;

/**
 * DROP TABLE statement
 *
 * <dl>
 * <dt>SQLite</dt>
 * <dd>https://www.sqlite.org/lang_droptable.html</dd>
 * <dt>PostgreSQL</dt>
 * <dd>https://www.postgresql.org/docs/7.4/sql-droptable.html</dd>
 * <dt>MySQL</dt>
 * <dd>https://mariadb.com/kb/en/drop-table/</dd>
 * </dl>
 */
class DropTableQuery extends Statement
{
	use StatementTableTrait;

	/**
	 * Force to drop table and all elements related to it.
	 *
	 * @var integer
	 */
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

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();

		$cascade = ($this->dropFlags & self::CASCADE) &&
			$platform->queryFeature(
				[
					K::PLATFORM_FEATURE_DROP,
					K::PLATFORM_FEATURE_CASCADE
				], false);

		$existsCondition = $platform->queryFeature(
			[
				K::PLATFORM_FEATURE_DROP,
				K::PLATFORM_FEATURE_TABLE,
				K::PLATFORM_FEATURE_EXISTS_CONDITION
			], false);

		$context->setStatementType(K::QUERY_DROP_TABLE);

		$stream->keyword('drop')
			->space()
			->keyword('table');

		if ($existsCondition)
		{
			$stream->space()
				->keyword('if')
				->space()
				->keyword('exists');
		}

		$stream->space()->identifier(
			$context->getPlatform()
				->quoteIdentifierPath(
				$this->getTable()
					->getPathParts()));

		if ($this->dropFlags & self::CASCADE)
		{
			if ($cascade)
				$stream->space()->keyword('cascade');
			elseif ($context instanceof LoggerInterface)
				$context->notice('CASCADE option is not supported');
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
