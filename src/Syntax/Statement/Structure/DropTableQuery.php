<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\StructureElementIdentifier;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\Statement;
use NoreSources\SQL\Syntax\Statement\Traits\StatementTableTrait;
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
	 * @return \NoreSources\SQL\Syntax\Statement\Structure\DropTableQuery
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
					K::FEATURE_DROP,
					K::FEATURE_CASCADE
				], false);

		$existsCondition = $platform->queryFeature(
			[
				K::FEATURE_DROP,
				K::FEATURE_TABLE,
				K::FEATURE_EXISTS_CONDITION
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
