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
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\TypeInterface;
use NoreSources\SQL\Expression\Evaluator;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Statement\StatementException;
use NoreSources\SQL\Statement\Structure\CreateTableQuery;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
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

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$builder = $context->getStatementBuilder();

		$structure = $this->getStructure();
		if (!($structure instanceof TableStructure))
			$structure = $context->getPivot();

		if (!($structure instanceof TableStructure &&
			($structure->count() > 0)))
			throw new StatementException($this,
				'Missing or invalid table structure');

		$primaryKeyColumns = [];
		$autoIncrementPrimaryKey = false;

		foreach ($structure->getConstraints() as $contraint)
		{
			if ($contraint instanceof PrimaryKeyTableConstraint)
				$primaryKeyColumns = $contraint->getColumns();
		}

		$context->pushResolverContext($structure);
		$context->setStatementType(K::QUERY_CREATE_TABLE);

		$stream->keyword('create')
			->space()
			->keyword('table')
			->space()
			->keyword('if not exists');

		$stream->space()
			->identifier(
			$builder->getCanonicalName($this->getStructure()))
			->space()
			->text('(');

		$c = 0;
		foreach ($this->getStructure() as $name => $column)
		{
			/**
			 *
			 * @var \NoreSources\SQL\Structure\ColumnStructure $column
			 */

			$columnFlags = $column->getColumnProperty(K::COLUMN_FLAGS);

			$isPrimary = Container::keyExists($primaryKeyColumns, $name);

			if ($c++ > 0)
				$stream->text(',')->space();

			$type = $builder->getPlatform()->getColumnType($column,
				$column->getConstraintFlags());
			/**
			 *
			 * @var TypeInterface $type
			 */

			$typeName = $type->getTypeName();

			$stream->identifier(
				$builder->escapeIdentifier($column->getName()))
				->space()
				->identifier($typeName);

			if ($columnFlags & K::COLUMN_FLAG_AUTO_INCREMENT)
			{
				if (!$isPrimary)
					throw new StatementException($this,
						'Auto increment column "' . $column->getName() .
						'" must be the primary key');
				if (Container::count($primaryKeyColumns) != 1)
					throw new StatementException($this,
						'Table "' . $structure->getName() .
						'" cannot have a composite primary key with auto increment column');

				$autoIncrementPrimaryKey = true;
				$stream->space()
					->keyword('primary key')
					->space()
					->keyword(
					$builder->getPlatform()
						->getKeyword(K::KEYWORD_AUTOINCREMENT));
			}

			if (!($columnFlags & K::COLUMN_FLAG_NULLABLE))
			{
				$stream->space()
					->keyword('NOT')
					->space()
					->keyword('NULL');
			}

			if ($column->hasColumnProperty(K::COLUMN_DEFAULT_VALUE))
			{
				$v = Evaluator::evaluate(
					$column->getColumnProperty(K::COLUMN_DEFAULT_VALUE));
				$stream->space()
					->keyword('DEFAULT')
					->space()
					->expression($v, $context);
			}
		}

		// Constraints
		foreach ($structure->getConstraints() as $constraint)
		{
			if ($constraint instanceof PrimaryKeyTableConstraint &&
				$autoIncrementPrimaryKey)
				continue;

			if ($c++ > 0)
				$stream->text(',')->space();

			$this->tokenizeTableConstraint($constraint, $stream,
				$context);
		} // constraints

		$stream->text(')');
		$context->popResolverContext();
		return $stream;
	}
}
