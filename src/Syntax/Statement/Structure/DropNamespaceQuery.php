<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\PlatformInterface;

/**
 * DROP NAMESPACE statement
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
class DropNamespaceQuery extends DropStructureElementQuery
{

	public function getStatementType()
	{
		return K::QUERY_DROP_NAMESPACE;
	}

	public function getStructureTypeName(
		PlatformInterface $platform = null)
	{
		if (isset($platform))
			return $platform->getKeyword(K::KEYWORD_NAMESPACE);
		return K::STRUCTURE_NAMESPACE;
	}
}