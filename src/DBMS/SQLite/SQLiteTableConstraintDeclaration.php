<?php
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\TableConstraintDeclaration;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;

class SQLiteTableConstraintDeclaration extends TableConstraintDeclaration
{

	/**
	 * On SQLite, PRIMARY KEY with AUTO INCREMENT constraint
	 * is declared at column declaration level.
	 * So, a single column primary key with auto increment is ignored (see SQLiteCreateTableQuery)
	 * and multi-column primary key with one auto increment column is translated to a UNIQUE
	 * constraint.
	 */
	protected function getColumnTableConstraintNameKeyword()
	{
		$constraint = $this->getConstraint();
		if ($constraint instanceof PrimaryKeyTableConstraint)
		{
			foreach ($constraint->getColumns() as $column)
			{
				if ($column->hasColumnProperty(K::COLUMN_FLAGS))
				{
					if ($column->getColumnProperty(K::COLUMN_FLAGS) &
						K::COLUMN_FLAG_AUTO_INCREMENT)
						return 'unique';
				}
			}
		}

		return parent::getColumnTableConstraintNameKeyword();
	}
}
