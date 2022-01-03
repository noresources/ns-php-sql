<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\ColumnDescriptionInterface;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Syntax\TableConstraintDeclaration;

class SQLiteTableConstraintDeclaration extends TableConstraintDeclaration
{

	/**
	 * On SQLite, PRIMARY KEY with AUTO INCREMENT constraint
	 * is declared at column declaration level.
	 * So, a single column primary key with auto increment is ignored (see SQLiteCreateTableQuery)
	 * and multi-column primary key with one auto increment column is translated to a UNIQUE
	 * constraint.
	 */
	protected function getKeyTableConstraintNameKeyword()
	{
		$constraint = $this->getConstraint();

		if ($constraint instanceof PrimaryKeyTableConstraint)
		{
			$table = $this->getTable();
			foreach ($constraint->getColumns() as $column)
			{
				if (!($column instanceof ColumnDescriptionInterface))
					$column = $table->getColumns()->get($column);
				if ($column->has(K::COLUMN_FLAGS))
				{
					if ($column->get(K::COLUMN_FLAGS) &
						K::COLUMN_FLAG_AUTO_INCREMENT)
						return 'unique';
				}
			}
		}

		return parent::getKeyTableConstraintNameKeyword();
	}
}
