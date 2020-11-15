<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\Container;
use NoreSources\SQL\DBMS\SQLite\SQLiteConstants as K;
use NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\TableConstraint;
use NoreSources\SQL\Structure\TableStructure;

/**
 * CREATE TABLE query for SQLite dialect
 *
 * SQLite has a special syntax and restrictions for primary column with auto-increment.
 *
 * @see https://sqlite.org/lang_createtable.html
 */
class SQLiteCreateTableQuery extends CreateTableQuery
{

	/**
	 *
	 * @param TableStructure $table
	 */
	public function __construct(TableStructure $table = null)
	{
		parent::__construct($table);
	}

	/**
	 * PRIMARY KEY with a AUTO INCREMENT column is declared at column declaration level.
	 */
	protected function acceptTableConstraint(
		TableConstraint $constraint)
	{
		if ($constraint instanceof PrimaryKeyTableConstraint)
		{

			$columns = $constraint->getColumns();
			foreach ($columns as $column)
			{
				$flags = $column->get(K::COLUMN_FLAGS);

				if ($flags & K::COLUMN_FLAG_AUTO_INCREMENT)
					return Container::count($columns) > 1;
			}
		}

		return true;
	}
}
