<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\DropFlagsTrait;
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
class DropTableQuery implements TokenizableStatementInterface
{
	use StatementTableTrait;
	use DropFlagsTrait;

	/**
	 * Force to drop table and all elements related to it.
	 *
	 * @var integer
	 * @deprecated Use K::DROP_CASCADE
	 */
	const CASCADE = K::DROP_CASCADE;

	/**
	 *
	 * @param Identifier|TableStructure|string $identifier
	 *        	Table identifier
	 */
	public function __construct($identifier = null)
	{
		if ($identifier != null)
			$this->table($identifier);
	}

	public function getStatementType()
	{
		return K::QUERY_DROP_TABLE;
	}

	/**
	 * Set DROP TABLE option flags
	 *
	 * @param integer $flags
	 * @return \NoreSources\SQL\Syntax\Statement\Structure\DropTableQuery
	 *
	 * @deprecated Use dropFlags()
	 */
	public function flags($flags)
	{
		return $this->dropFlags($flags);
	}

	/**
	 *
	 * @return number
	 * @deprecated return getDropFlags()
	 */
	public function getFlags()
	{
		return $this->getDropFlags();
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();
		$platformDropFlags = $platform->queryFeature(
			[
				K::FEATURE_DROP,
				K::FEATURE_TABLE,
				K::FEATURE_DROP_FLAGS
			], 0);

		$cascade = ($this->getDropFlags() & K::DROP_CASCADE) &&
			($platformDropFlags & K::FEATURE_DROP_CASCADE);

		$stream->keyword('drop')
			->space()
			->keyword('table');

		if (($platformDropFlags & K::FEATURE_DROP_EXISTS_CONDITION))
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

		if ($this->getDropFlags() & K::DROP_CASCADE)
		{
			if ($cascade)
				$stream->space()->keyword('cascade');
			elseif ($context instanceof LoggerInterface)
				$context->notice('CASCADE option is not supported');
		}

		return $stream;
	}
}
