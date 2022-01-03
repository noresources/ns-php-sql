<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\Container\Container;
use NoreSources\Expression\ExpressionInterface;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\IndexDescriptionInterface;
use NoreSources\SQL\Structure\IndexStructure;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\Traits\IndexDescriptionTrait;
use NoreSources\SQL\Syntax\TableReference;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\StatementException;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\CreateFlagsTrait;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\IdentifierPropertyTrait;
use NoreSources\SQL\Syntax\Statement\Traits\IdenitifierTokenizationTrait;
use NoreSources\Type\TypeConversion;
use NoreSources\Type\TypeDescription;

/**
 * CREATE INDEX statement
 *
 * @see https://www.sqlite.org/lang_createindex.html
 */
class CreateIndexQuery implements TokenizableStatementInterface,
	IndexDescriptionInterface, StructureOperationQueryInterface
{
	use IndexDescriptionTrait;
	use CreateFlagsTrait;
	use IdenitifierTokenizationTrait;
	use IdentifierPropertyTrait;

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
	 *        	Index name
	 * @throws \InvalidArgumentException
	 * @return $this
	 * @deprecated Use forStructure
	 *
	 */
	public function setFromTable(TableStructure $table, $identifier)
	{
		$index = Container::firstValue(
			Container::filter(
				$table->getChildElements(IndexStructure::class),
				function ($k, $v) use ($identifier) {
					if (\is_integer($identifier) && $k == $identifier)
						return true;
					if ($v->getName() == $identifier)
						return true;
					return false;
				}));

		if (!($index instanceof IndexStructure))
			throw new \InvalidArgumentException(
				$identifier . ' index not found');

		$this->forStructure($index);
		return $this;
	}

	public function identifier($identifier)
	{
		/**
		 *
		 * @note By desing IndexStructure are generally child of TableStructure
		 * but they must be declared in Namespace
		 */
		if ($identifier instanceof IndexStructure)
		{
			$index = $identifier;
			$ns = $index->getParentElement();
			if ($ns instanceof TableStructure)
				$ns = $ns->getParentElement();

			/** @var Identifier $identifier */
			$identifier = Identifier::make($ns);
			$identifier->append($index->getName());
		}

		$this->structureIdentifier = Identifier::make($identifier);
		return $this;
	}

	public function forStructure(StructureElementInterface $index)
	{
		if (!($index instanceof IndexStructure))
			throw new \InvalidArgumentException(
				IndexStructure::class . ' expected, got ' .
				TypeDescription::getName($index));

		/** @var IndexStructure $index */

		$columns = $index->getColumns();
		$table = $index->getParentElement();
		$this->indexColumns = [];

		$this->identifier($index)
			->table($table)
			->columns(...$columns)
			->flags($index->getIndexFlags());

		$this->whereConstraints = $index->getConstraintExpression();
		if (isset($this->whereConstraints))
			$this->whereConstraints = clone $this->whereConstraints;
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
				K::FEATURE_ELEMENT_INDEX,
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

		if (($this->getCreateFlags() & K::CREATE_EXISTS_CONDITION) &&
			($platformCreateFlags & K::FEATURE_CREATE_EXISTS_CONDITION))
			$stream->space()
				->keyword('if')
				->space()
				->keyword('not')
				->space()
				->keyword('exists');

		$identifier = $this->getIdentifier();
		if (isset($identifier))
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

		if (isset($this->whereConstraints) &&
			Container::count($this->whereConstraints))
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
	 * @var \NoreSources\SQL\Syntax\TableReference
	 */
	private $indexTable;
}