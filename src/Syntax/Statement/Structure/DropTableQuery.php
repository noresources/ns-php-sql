<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\PlatformInterface;

/**
 * DROP TABLE statement
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
class DropTableQuery extends DropStructureElementQuery
{

	public function getStatementType()
	{
		return K::QUERY_DROP_TABLE;
	}

	public function getStructureTypeName(
		PlatformInterface $platform = null)
	{
		return K::STRUCTURE_TABLE;
	}
}