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
namespace NoreSources\SQL\Statement\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\TypeHelper;
use NoreSources\SQL\DBMS\TypeInterface;
use NoreSources\SQL\Expression\Evaluator;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Statement\StatementException;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\ColumnTableConstraint;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\StructureProviderInterface;
use NoreSources\SQL\Structure\TableConstraint;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\UniqueTableConstraint;

/**
 * CREATE TABLE statement
 */
class CreateTableQuery extends Statement implements
	StructureProviderInterface
{

	const REPLACE = 0x01;

	const TEMPORARY = 0x02;

	/**
	 *
	 * @param TableStructure $structure
	 *        	Table structire to create
	 */
	public function __construct(TableStructure $structure = null)
	{
		if ($structure instanceof TableStructure)
			$this->table($structure);

		$this->createFlags = 0;
	}

	public function getStructure()
	{
		return $this->structure;
	}

	/**
	 * Set the table structure
	 *
	 * @param TableStructure $table
	 * @return \NoreSources\SQL\Statement\Structure\CreateTableQuery
	 */
	public function table(TableStructure $table)
	{
		$this->structure = $table;
		return $this;
	}

	/**
	 * Set modifiers
	 *
	 * @param integer $flags
	 * @return \NoreSources\SQL\Statement\Structure\CreateTableQuery
	 */
	public function flags($flags)
	{
		$this->createFlags = $flags;
		return $this;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$builder = $context->getStatementBuilder();
		$platform = $builder->getPlatform();

		$existsCondition = $platform->queryFeature(
			[
				K::PLATFORM_FEATURE_CREATE,
				K::PLATFORM_FEATURE_TABLE,
				K::PLATFORM_FEATURE_EXISTS_CONDITION
			], false);

		$replaceSupport = $platform->queryFeature(
			[
				K::PLATFORM_FEATURE_CREATE,
				K::PLATFORM_FEATURE_TABLE,
				K::PLATFORM_FEATURE_REPLACE
			], false);

		$temporarySupport = $platform->queryFeature(
			[
				K::PLATFORM_FEATURE_CREATE,
				K::PLATFORM_FEATURE_TABLE,
				K::PLATFORM_FEATURE_TEMPORARY
			], false);

		$structure = $this->structure;
		if (!($structure instanceof TableStructure))
			$structure = $context->getPivot();

		if (!($structure instanceof TableStructure &&
			($structure->count() > 0)))
			throw new StatementException($this,
				'Missing or invalid table structure');

		$primaryKeyColumns = [];
		foreach ($structure->getConstraints() as $contraint)
		{
			if ($contraint instanceof PrimaryKeyTableConstraint)
				$primaryKeyColumns = $contraint->getColumns();
		}

		$context->pushResolverContext($structure);
		$context->setStatementType(K::QUERY_CREATE_TABLE);

		$stream->keyword('create');
		if (($this->createFlags & self::REPLACE) && $replaceSupport)
			$stream->space()
				->keyword('or')
				->space()
				->keyword('replace');
		if (($this->createFlags & self::TEMPORARY) && $temporarySupport)
			$stream->space()->keyword('temporary');
		$stream->space()->keyword('table');

		if ($existsCondition)
			$stream->space()->keyword('if not exists');

		$stream->space()
			->identifier(
			$context->getStatementBuilder()
				->getCanonicalName($this->structure))
			->space()
			->text('(');

		// Columns

		$c = 0;
		foreach ($this->structure as $column)
		{
			if ($c++ > 0)
				$stream->text(',')->space();

			$this->tokenizeColumnDescription($column, $stream, $context);
		}

		// Constraints
		foreach ($structure->getConstraints() as $constraint)
		{
			if ($c++ > 0)
				$stream->text(',')->space();

			$this->tokenizeTableConstraint($constraint, $stream,
				$context);
		} // constraints

		$stream->text(')');
		$context->popResolverContext();
		return $stream;
	}

	protected function tokenizeColumnDescription(
		ColumnStructure $column, TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$builder = $context->getStatementBuilder();
		$platform = $builder->getPlatform();

		$columnDeclaration = $platform->queryFeature(
			[
				K::PLATFORM_FEATURE_CREATE,
				K::PLATFORM_FEATURE_TABLE,
				K::PLATFORM_FEATURE_COLUMN_DECLARATION_FLAGS
			], 0);

		$isPrimary = (($column->getConstraintFlags() &
			K::COLUMN_CONSTRAINT_PRIMARY_KEY) ==
			K::COLUMN_CONSTRAINT_PRIMARY_KEY);

		$columnFlags = $column->getColumnProperty(K::COLUMN_FLAGS);

		$type = $context->getStatementBuilder()->getColumnType($column);
		if (!($type instanceof TypeInterface))
			throw new StatementException($this,
				'Unable to find a DBMS type for column "' .
				$column->getName() . '"');
		/**
		 *
		 * @var TypeInterface $type
		 */

		$typeName = $type->getTypeName();

		$stream->identifier(
			$context->getStatementBuilder()
				->escapeIdentifier($column->getName()))
			->space()
			->identifier($typeName);

		$typeFlags = TypeHelper::getProperty($type, K::TYPE_FLAGS);

		$lengthSupport = (($typeFlags & K::TYPE_FLAG_LENGTH) ==
			K::TYPE_FLAG_LENGTH);

		$fractionScaleSupport = (($typeFlags &
			K::TYPE_FLAG_FRACTION_SCALE) == K::TYPE_FLAG_FRACTION_SCALE);

		$hasLength = $column->hasColumnProperty(K::COLUMN_LENGTH);
		$hasFractionScale = $column->hasColumnProperty(
			K::COLUMN_FRACTION_SCALE);

		$hasEnumeration = $column->hasColumnProperty(
			K::COLUMN_ENUMERATION);

		if ($hasEnumeration &&
			($columnDeclaration & K::PLATFORM_FEATURE_COLUMN_ENUM))
		{
			$stream->text('(');
			$values = $column->getColumnProperty(K::COLUMN_ENUMERATION);
			$i = 0;
			foreach ($values as $value)
			{
				if ($i++ > 0)
					$stream->text(', ');
				$stream->expression($value, $context);
			}
			$stream->text(')');
		}
		elseif ($hasLength && $lengthSupport)
		{
			$stream->text('(')->literal(
				$column->getColumnProperty(K::COLUMN_LENGTH));

			if ($column->hasColumnProperty(K::COLUMN_FRACTION_SCALE) &&
				$fractionScaleSupport)
			{
				$stream->text(', ')->literal(
					$column->getColumnProperty(K::COLUMN_FRACTION_SCALE));
			}

			$stream->text(')');
		}
		elseif ($hasFractionScale && $fractionScaleSupport)
		{
			$scale = $column->getColumnProperty(
				K::COLUMN_FRACTION_SCALE);
			$length = TypeHelper::getMaxLength($type);
			if (\is_infinite($length))
			{
				/**
				 *
				 * @todo trigger warning
				 */
				$length = $scale * 2;
			}
			$stream->text('(')
				->literal($length)
				->text(',')
				->literal($scale)
				->text(')');
		}
		elseif (($typeFlags & K::TYPE_FLAG_MANDATORY_LENGTH) ||
			($isPrimary &&
			($columnDeclaration &
			K::PLATFORM_FEATURE_COLUMN_KEY_MANDATORY_LENGTH)))
		{
			$maxLength = TypeHelper::getMaxLength($type);
			if (\is_infinite($maxLength))
				throw new StatementException($this,
					$column->getName() .
					' column require length specification but type ' .
					$type->getTypeName() . ' max length is unspecified');

			$stream->text('(')
				->literal($maxLength)
				->text(')');
		}

		if (($typeFlags & K::TYPE_FLAG_SIGNNESS) &&
			($columnFlags & K::COLUMN_FLAG_UNSIGNED))
			$stream->space()->keyword('unsigned');

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

		if ($columnFlags & K::COLUMN_FLAG_AUTO_INCREMENT)
		{
			$ai = $context->getStatementBuilder()
				->getPlatform()
				->getKeyword(K::KEYWORD_AUTOINCREMENT);
			if (\strlen($ai))
				$stream->space()->keyword($ai);
		}
	}

	protected function tokenizeTableConstraint(
		TableConstraint $constraint, TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$structure = $this->structure;
		if (!($structure instanceof TableStructure))
			$structure = $context->getPivot();

		if (\strlen($constraint->constraintName))
		{
			$stream->keyword('constraint')
				->space()
				->identifier(
				$context->getStatementBuilder()
					->escapeIdentifier($constraint->constraintName));
		}

		if ($constraint instanceof ColumnTableConstraint)
		{
			if ($constraint instanceof PrimaryKeyTableConstraint)
				$stream->keyword('primary key');
			elseif ($constraint instanceof UniqueTableConstraint)
				$stream->keyword('unique');

			$stream->space()->text('(');
			$i = 0;
			foreach ($constraint as $column)
			{
				if ($i++ > 0)
					$stream->text(',')->space();

				$stream->identifier(
					$context->getStatementBuilder()
						->escapeIdentifier($column->getName()));
			}
			$stream->text(')');
		}
		elseif ($constraint instanceof ForeignKeyTableConstraint)
		{
			$stream->keyword('foreign key')
				->space()
				->text('(');

			$i = 0;
			foreach ($constraint as $column => $reference)
			{
				if ($i++ > 0)
					$stream->text(',')->space();

				$stream->identifier(
					$context->getStatementBuilder()
						->escapeIdentifier($column));
			}
			$stream->text(')');

			$stream->space()
				->keyword('references')
				->space();

			$ft = $constraint->getForeignTable();
			if ($ft->getParentElement() == $structure->getParentElement())
				$stream->identifier(
					$context->getStatementBuilder()
						->escapeIdentifier($ft->getName()));
			else

				$stream->identifier(
					$context->getStatementBuilder()
						->getCanonicalName(
						$constraint->getForeignTable()));

			$stream->space()->text('(');

			$i = 0;
			foreach ($constraint as $column => $reference)
			{
				if ($i++ > 0)
					$stream->text(',')->space();
				$stream->identifier(
					$context->getStatementBuilder()
						->escapeIdentifier($reference));
			}
			$stream->text(')');

			if ($constraint->onUpdate)
			{
				$stream->space()
					->keyword('on update')
					->space()
					->keyword(
					$context->getStatementBuilder()
						->getPlatform()
						->getForeignKeyAction($constraint->onUpdate));
			}

			if ($constraint->onDelete)
			{
				$stream->space()
					->keyword('on delete')
					->space()
					->keyword(
					$context->getStatementBuilder()
						->getPlatform()
						->getForeignKeyAction($constraint->onDelete));
			}
		}
	}

	/**
	 *
	 * @var TableStructure
	 */
	private $structure;

	/**
	 *
	 * @var integer
	 */
	private $createFlags;
}
