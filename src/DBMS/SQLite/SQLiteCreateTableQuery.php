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
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\TableConstraintInterface;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery;

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
		TableConstraintInterface $constraint)
	{
		if ($constraint instanceof PrimaryKeyTableConstraint)
		{
			$table = $this->getTable();
			$columns = $constraint->getColumns();
			foreach ($columns as $name)
			{
				$column = $table->getColumn($name);
				$flags = $column->get(K::COLUMN_FLAGS);

				if ($flags & K::COLUMN_FLAG_AUTO_INCREMENT)
					return (Container::count($columns) > 1);
			}
		}

		return parent::acceptTableConstraint($constraint);
	}
}
