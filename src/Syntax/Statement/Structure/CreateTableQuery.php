<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataTypeDescription;
use NoreSources\SQL\DBMS\TypeInterface;
use NoreSources\SQL\Structure\KeyTableConstraintInterface;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureProviderInterface;
use NoreSources\SQL\Structure\TableConstraintInterface;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\ColumnDeclaration;
use NoreSources\SQL\Syntax\TableConstraintDeclaration;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\StatementException;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\CreateFlagsTrait;
use NoreSources\Type\TypeDescription;

/**
 * CREATE TABLE statement
 *
 * <dl>
 * <dt>MySQL</dt>
 * <dd>https://dev.mysql.com/doc/refman/8.0/en/create-table.html</dd>
 * <dt>SQLite</dt>
 * <dd>https://sqlite.org/lang_createtable.html</dd>
 * <dt>PostgreSQL</dt>
 * <dd>https://www.postgresql.org/docs/7.1/sql-createtable.html</dd>
 * </dl>
 */
class CreateTableQuery implements TokenizableStatementInterface,
	StructureProviderInterface, StructureOperationQueryInterface
{

	/**
	 *
	 * @deprecated use K::CREATE_REPLACE
	 */
	const REPLACE = K::CREATE_REPLACE;

	/**
	 *
	 * @deprecated use K::CREATE_TEMPORARY
	 */
	const TEMPORARY = K::CREATE_TEMPORARY;

	use CreateFlagsTrait;

	/**
	 *
	 * @param TableStructure $structure
	 *        	Table structire to create
	 */
	public function __construct(TableStructure $structure = null)
	{
		if ($structure instanceof TableStructure)
			$this->table($structure);
	}

	public function getStatementType()
	{
		return K::QUERY_CREATE_TABLE;
	}

	public function getStructure()
	{
		return $this->structure;
	}

	public function forStructure(
		StructureElementInterface $tableStructure)
	{
		return $this->table($tableStructure);
	}

	/**
	 * Set the table structure
	 *
	 * @param TableStructure $table
	 * @return \NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery
	 */
	public function table(TableStructure $table)
	{
		$this->structure = $table;
		return $this;
	}

	/**
	 *
	 * @return \NoreSources\SQL\Structure\TableStructure
	 */
	public function getTable()
	{
		return $this->structure;
	}

	/**
	 *
	 * @deprecated Use createFlags()
	 * @param integer $flags
	 * @return \NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery
	 */
	public function flags($flags)
	{
		return $this->createFlags($flags);
	}

	/**
	 *
	 * @deprecated Use getCreateFlags()
	 * @return unknown
	 */
	public function getFlags()
	{
		return $this->getCreateFlags();
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();

		$platformCreateFlags = $platform->queryFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_ELEMENT_TABLE,
				K::FEATURE_CREATE_FLAGS
			], 0);

		$platformCreateFlags = $platform->queryFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_ELEMENT_TABLE,
				K::FEATURE_CREATE_FLAGS
			], 0);

		$structure = $this->structure;
		if (!($structure instanceof TableStructure))
			$structure = $context->getPivot();

		if (!($structure instanceof TableStructure &&
			($structure->getColumns()->count() > 0)))
			throw new StatementException($this,
				'Missing or invalid table structure');

		$context->pushResolverContext($structure);

		$stream->keyword('create');
		if (($this->getCreateFlags() & K::CREATE_REPLACE) &&
			($platformCreateFlags & K::FEATURE_CREATE_REPLACE))
			$stream->space()
				->keyword('or')
				->space()
				->keyword('replace');
		if (($this->getCreateFlags() & K::CREATE_TEMPORARY) &&
			($platformCreateFlags & K::FEATURE_CREATE_TEMPORARY))
			$stream->space()->keyword(K::KEYWORD_TEMPORARY);
		$stream->space()->keyword('table');

		if (($this->getCreateFlags() & K::CREATE_EXISTS_CONDITION) &&
			($platformCreateFlags & K::FEATURE_CREATE_EXISTS_CONDITION))
			$stream->space()
				->keyword('if')
				->space()
				->keyword('not')
				->space()
				->keyword('exists');

		$stream->space()
			->identifier(
			$platform->quoteIdentifierPath($this->structure))
			->space()
			->text('(');

		// Columns

		$c = 0;
		foreach ($this->structure->getColumns() as $column)
		{
			if ($c++ > 0)
				$stream->text(',')->space();

			$constraintFlags = $this->structure->getColumnConstraintFlags(
				$column->getName());

			$type = $platform->getColumnType($column, $constraintFlags);

			if (!($type instanceof TypeInterface))
			{
				$dataType = K::DATATYPE_UNDEFINED;
				if ($column->has(K::COLUMN_DATA_TYPE))
				{
					$dataType = $column->get(K::COLUMN_DATA_TYPE);
				}
				$dataType = DataTypeDescription::getInstance()->getNames(
					$dataType)[0];

				throw new StatementException($this,
					'Unable to find a ' .
					TypeDescription::getLocalName($platform) .
					' type for column "' . $column->getName() . '" (' .
					$dataType . ')');
			}

			$declaration = $platform->newExpression(
				ColumnDeclaration::class, $column, $type);
			$declaration->tokenize($stream, $context);
		}

		// Constraints
		foreach ($structure->getConstraints() as $constraint)
		{
			if (!$this->acceptTableConstraint($constraint))
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

	/**
	 * Indicates if the constraint can be declared in a CREATE TABLE statement.
	 *
	 * @param TableConstraintInterface $constraint
	 *        	Table constraint
	 * @return boolean
	 */
	protected function acceptTableConstraint(
		TableConstraintInterface $constraint)
	{
		if ($constraint instanceof KeyTableConstraintInterface)
		{
			return (($constraint->getIndexFlags() & K::INDEX_UNIQUE) ==
				K::INDEX_UNIQUE);
		}

		return true;
	}

	protected function tokenizeTableConstraint(
		TableConstraintInterface $constraint, TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$declaration = $context->getPlatform()->newExpression(
			TableConstraintDeclaration::class, $this->structure,
			$constraint);

		return $declaration->tokenize($stream, $context);
	}

	/**
	 *
	 * @var TableStructure
	 */
	private $structure;
}
