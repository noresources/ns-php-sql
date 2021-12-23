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
class DropViewQuery extends DropStructureElementQuery
{

	public function getStatementType()
	{
		return K::QUERY_DROP_VIEW;
	}

	public function getStructureTypeName(
		PlatformInterface $platform = null)
	{
		return K::STRUCTURE_VIEW;
	}
}