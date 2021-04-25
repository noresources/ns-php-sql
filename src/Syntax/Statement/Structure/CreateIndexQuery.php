<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\Container;
use NoreSources\TypeConversion;
use NoreSources\TypeDescription;
use NoreSources\Expression\ExpressionInterface;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\IndexDescriptionInterface;
use NoreSources\SQL\Structure\IndexTableConstraintInterface;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\Traits\IndexDescriptionTrait;
use NoreSources\SQL\Syntax\TableReference;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\StatementException;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\CreateFlagsTrait;
use NoreSources\SQL\Syntax\Statement\Traits\IdenitifierTokenizationTrait;

/**
 * CREATE INDEX statement
 *
 * @see https://www.sqlite.org/lang_createindex.html
 */
class CreateIndexQuery implements TokenizableStatementInterface,
	IndexDescriptionInterface
{
	use IndexDescriptionTrait;
	use CreateFlagsTrait;
	use IdenitifierTokenizationTrait;

	/**
	 *
	 * @param Identifier $identifier
	 */
	public function __construct($identifier = null)
	{
		$this->indexTable = null;
		$this->initializeWhereConstraints();

		if ($identifier !== null)
			$this->identifier($identifier);
	}

	public function getStatementType()
	{
		return K::QUERY_CREATE_INDEX;
	}

	/**
	 *
	 * @param TableStructure $table
	 *        	Table
	 * @param string|integer $identifier
	 *        	Table constraint name or index
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function setFromTable(TableStructure $table, $identifier)
	{
		$index = Container::firstValue(
			Container::filter($table->getConstraints(),
				function ($k, $v) use ($identifier) {
					if (\is_integer($identifier) && $k == $identifier)
						return true;
					if ($v->getName() == $identifier)
						return true;
					return false;
				}));

		if (!($index instanceof IndexTableConstraintInterface))
			throw new \InvalidArgumentException(
				$identifier . ' index not found');

		$columns = $index->getColumns();
		$this->indexColumns = [];

		$this->identifier($index)
			->table($table)
			->columns(...$columns)
			->flags($index->getIndexFlags());

		return $this;
	}

	/**
	 *
	 * @param Identifier|string $identifier
	 *        	Index identifier
	 *
	 * @return $this
	 */
	public function identifier($identifier)
	{
		$this->indexIdentifier = Identifier::make($identifier);

		return $this;
	}

	/**
	 *
	 * @param TableReference|TableStructure|string $table
	 *        	Table reference
	 *
	 * @return $this
	 */
	public function table($table)
	{
		if ($table instanceof TableReference)
			$this->indexTable = $table;
		elseif ($table instanceof TableStructure)
			$this->indexTable = new TableReference(
				$table->getIdentifier());
		elseif (TypeDescription::hasStringRepresentation($table))
			$this->indexTable = new TableReference(
				TypeConversion::toString($table));
		else
			throw new StatementException($this,
				'Invalid table argument. ' . TableReference::class . ', ' .
				TableStructure::class . ' or string expected. Got ' .
				TypeDescription::getName($table));

		return $this;
	}

	/**
	 *
	 * @return \NoreSources\SQL\Structure\Identifier
	 */
	public function getIdentifier()
	{
		return $this->indexIdentifier;
	}

	/**
	 *
	 * @return \NoreSources\SQL\Syntax\TableReference
	 */
	public function getTable()
	{
		return $this->indexTable;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();

		$platformCreateFlags = $platform->queryFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_INDEX,
				K::FEATURE_CREATE_FLAGS
			], 0);

		$tableStructure = $context->findTable(
			\strval($this->indexTable));
		$context->pushResolverContext($tableStructure);

		/**
		 *
		 * @var TableStructure $tableStructure
		 */

		$stream->keyword('create');
		if ($this->getIndexFlags() & K::INDEX_UNIQUE)
			$stream->space()->keyword('unique');
		$stream->space()->keyword('index');

		if (($platformCreateFlags & K::FEATURE_CREATE_EXISTS_CONDITION))
			$stream->space()
				->keyword('if')
				->space()
				->keyword('not')
				->space()
				->keyword('exists');

		if (isset($this->indexIdentifier))
		{
			$stream->space();
			$this->tokenizeIndexIdentifier($stream, $context);
		}

		$stream->space()
			->keyword('on')
			->space();
		$this->tokznizeIndexTable($stream, $context);
		$stream->space()->text('(');

		$i = 0;
		$columns = $this->getColumns();
		foreach ($columns as $column)
		{
			if ($i++)
				$stream->text(',');

			if ($column instanceof ExpressionInterface)
				$stream->space()->expression($column, $context);
			else
				$stream->space()->identifier(
					$context->getPlatform()
						->quoteIdentifier($column));
		}

		$stream->text(')');

		if ($this->whereConstraints->count())
		{
			$stream->space()
				->keyword('where')
				->space()
				->constraints($this->whereConstraints, $context);
		}

		$context->popResolverContext();
		return $stream;
	}

	public function tokenizeIndexIdentifier(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();
		return $stream->identifier(
			$platform->quoteIdentifier(
				$this->getIdentifier()
					->getLocalName()));
	}

	public function tokznizeIndexTable(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$identifier = Identifier::make($this->indexTable);
		return $this->tokenizeIdentifier($stream, $context, $identifier,
			true);
	}

	/**
	 *
	 * @var Identifier
	 */
	private $indexIdentifier;

	/**
	 *
	 * @var \NoreSources\SQL\Syntax\TableReference
	 */
	private $indexTable;
}