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
use NoreSources\SQL\Expression\Evaluator;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContext;
use NoreSources\SQL\Statement\CreateTableQuery;
use NoreSources\SQL\Statement\StatementException;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\TableStructure;

class SQLiteCreateTableQuery extends CreateTableQuery
{

	public function __construct(TableStructure $table)
	{
		parent::__construct($table);
	}

	public function tokenize(TokenStream $stream, TokenStreamContext $context)
	{
		$builder = $context->getStatementBuilder();
		$builderFlags = $builder->getBuilderFlags(K::BUILDER_DOMAIN_GENERIC);
		$builderFlags |= $builder->getBuilderFlags(K::BUILDER_DOMAIN_CREATE_TABLE);

		$structure = $this->structure;
		if (!($structure instanceof TableStructure))
			$structure = $context->getPivot();

		if (!($structure instanceof TableStructure && ($structure->count() > 0)))
			throw new StatementException($this, 'Missing or invalid table structure');

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
			->identifier($builder->getCanonicalName($this->structure))
			->space()
			->text('(');

		$c = 0;
		foreach ($this->structure as $name => $column)
		{
			/**
			 *
			 * @var \NoreSources\SQL\Structure\ColumnStructure $column
			 */

			$columnFlags = $column->getColumnProperty(K::COLUMN_FLAGS);

			$isPrimary = Container::keyExists($primaryKeyColumns, $name);

			if ($c++ > 0)
				$stream->text(',')->space();

			$type = $builder->getColumnType($column);
			/**
			 *
			 * @var TypeInterface $type
			 */

			$typeName = $type->getTypeName();

			$stream->identifier($builder->escapeIdentifier($column->getName()))
				->space()
				->identifier($typeName);

			if ($columnFlags & K::COLUMN_FLAG_AUTO_INCREMENT)
			{
				if (!$isPrimary)
					throw new StatementException($this,
						'Auto increment column "' . $column->getName() . '" must be the primary key');
				if (Container::count($primaryKeyColumns) != 1)
					throw new StatementException($this,
						'Table "' . $structure->getName() .
						'" cannot have a composite primary key with auto increment column');

				$autoIncrementPrimaryKey = true;
				$stream->space()
					->keyword('primary key')
					->space()
					->keyword($builder->getKeyword(K::KEYWORD_AUTOINCREMENT));
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
			if ($constraint instanceof PrimaryKeyTableConstraint && $autoIncrementPrimaryKey)
				continue;

			if ($c++ > 0)
				$stream->text(',')->space();

			$this->tokenizeTableConstraint($constraint, $stream, $context);
		} // constraints

		$stream->text(')');
		$context->popResolverContext();
		return $stream;
	}
}