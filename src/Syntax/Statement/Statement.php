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
namespace NoreSources\SQL\Syntax\Statement;

use NoreSources\TypeConversion;
use NoreSources\TypeDescription;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Syntax\TokenizableExpressionInterface;
use NoreSources\SQL\Syntax\Statement\Manipulation\DeleteQuery;
use NoreSources\SQL\Syntax\Statement\Manipulation\InsertQuery;
use NoreSources\SQL\Syntax\Statement\Manipulation\UpdateQuery;
use NoreSources\SQL\Syntax\Statement\Query\SelectQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery;
use NoreSources\SQL\Syntax\Statement\Structure\DropTableQuery;

/**
 * SQL statement
 */
abstract class Statement implements TokenizableExpressionInterface
{

	/**
	 * Attempt to guess statement type from statement data
	 *
	 * @param mixed $data
	 * @return string|integer Statement type
	 */
	public static function statementTypeFromData($data)
	{
		if (\is_object($data))
		{
			if ($data instanceof SelectQuery)
				return K::QUERY_SELECT;
			elseif ($data instanceof InsertQuery)
				return K::QUERY_INSERT;
			elseif ($data instanceof UpdateQuery)
				return K::QUERY_UPDATE;
			elseif ($data instanceof DeleteQuery)
				return K::QUERY_DELETE;
			elseif ($data instanceof CreateTableQuery)
				return K::QUERY_CREATE_TABLE;
			elseif ($data instanceof DropTableQuery)
				return K::QUERY_DROP_TABLE;

			$type = 0;
			if ($data instanceof StatementOutputDataInterface)
				$type = $data->getStatementType();

			if ($type != 0)
				return $type;

			if (TypeDescription::hasStringRepresentation($data))
				$data = TypeConversion::toString($data);
		}

		if (\is_string($data))
		{
			$regex = [
				'/^select\s+/i' => K::QUERY_SELECT,
				'/^insert\s+/i' => K::QUERY_INSERT,
				'/^update\s+/i' => K::QUERY_UPDATE,
				'/^delete\s+/i' => K::QUERY_DELETE,
				'/^create\s+/i' => K::QUERY_FAMILY_CREATE
			];

			foreach ($regex as $r => $t)
			{
				if (preg_match($r, $data))
					return $t;
			}
		}

		return 0;
	}
}

