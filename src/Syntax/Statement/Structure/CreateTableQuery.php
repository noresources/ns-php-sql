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
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\TypeDescription;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\TypeInterface;
use NoreSources\SQL\Syntax\ColumnDeclaration;
use NoreSources\SQL\Syntax\TableConstraintDeclaration;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\Statement;
use NoreSources\SQL\Syntax\Statement\StatementException;
use NoreSources\SQL\Structure\StructureProviderInterface;
use NoreSources\SQL\Structure\TableConstraint;
use NoreSources\SQL\Structure\TableStructure;

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
	 * @return \NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery
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
	 * @return \NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery
	 */
	public function flags($flags)
	{
		$this->createFlags = $flags;
		return $this;
	}

	public function getFlags()
	{
		return $this->createFlags;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();

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

		$context->pushResolverContext($structure);
		$context->setStatementType(K::QUERY_CREATE_TABLE);

		$stream->keyword('create');
		if (($this->createFlags & self::REPLACE) && $replaceSupport)
			$stream->space()
				->keyword('or')
				->space()
				->keyword('replace');
		if (($this->createFlags & self::TEMPORARY) && $temporarySupport)
			$stream->space()->keyword(K::KEYWORD_TEMPORARY);
		$stream->space()->keyword('table');

		if ($existsCondition)
			$stream->space()->keyword('if not exists');

		$stream->space()
			->identifier(
			$platform->quoteIdentifierPath($this->structure))
			->space()
			->text('(');

		// Columns

		$c = 0;
		foreach ($this->structure as $column)
		{
			if ($c++ > 0)
				$stream->text(',')->space();

			$type = $platform->getColumnType($column,
				$column->getConstraintFlags());

			if (!($type instanceof TypeInterface))
				throw new StatementException($this,
					'Unable to find a ' .
					TypeDescription::getLocalName($platform) .
					' type for column "' . $column->getName() . '"');

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

	protected function acceptTableConstraint(
		TableConstraint $constraint)
	{
		return true;
	}

	protected function tokenizeTableConstraint(
		TableConstraint $constraint, TokenStream $stream,
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

	/**
	 *
	 * @var integer
	 */
	private $createFlags;
}
