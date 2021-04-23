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
use NoreSources\SQL\Structure\IndexDescriptionInterface;
use NoreSources\SQL\Structure\IndexTableConstraintInterface;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\Traits\IndexDescriptionTrait;
use NoreSources\SQL\Syntax\TableReference;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\StatementException;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;

/**
 * CREATE INDEX statement
 *
 * @see https://www.sqlite.org/lang_createindex.html
 */
class CreateIndexQuery implements TokenizableStatementInterface,
	IndexDescriptionInterface
{
	use IndexDescriptionTrait;

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
		$this->indexIdentifier = Identifier::make(
			$identifier);

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
			$this->indexTable = new TableReference($table->getPath());
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

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();
		$scoped = $platform->queryFeature(
			[
				K::FEATURE_INDEX,
				K::FEATURE_SCOPED
			], false);

		$existsCondition = $platform->queryFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_INDEX,
				K::FEATURE_EXISTS_CONDITION
			], false);

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

		if ($existsCondition)
			$stream->space()
				->keyword('if')
				->space()
				->keyword('not')
				->space()
				->keyword('exists');

		if ($this->indexIdentifier instanceof Identifier)
		{
			$stream->space();
			$this->tokenizeIndexIdentifier($stream, $context,
				$this->indexIdentifier, $tableStructure);
		}

		$stream->space()
			->keyword('on')
			->space();
		$this->tokenizeTableIdentifier($stream, $context,
			$tableStructure);
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

	public function getNamespaceName(
		TokenStreamContextInterface $context = null)
	{
		if (isset($this->indexIdentifier))
		{
			$parts = $this->indexIdentifier->getPathParts();
			if (\count($parts) > 1)
				return Container::firstValue($parts);
		}

		if (!$context)
			return null;

		$structure = $context->getPivot();
		if (!$structure)
			return null;

		if ($structure instanceof TableStructure)
			$structure = $structure->getParentElement();

		if ($structure instanceof NamespaceStructure)
			return $structure->getName();

		return null;
	}

	protected function tokenizeIndexIdentifier(TokenStream $stream,
		TokenStreamContextInterface $context,
		Identifier $identifier)
	{
		$platform = $context->getPlatform();
		$scoped = $platform->queryFeature(
			[
				K::FEATURE_INDEX,
				K::FEATURE_SCOPED
			], false);

		if ($scoped)
		{
			$namespace = $this->getNamespaceName($context);
			if ($namespace)
				$stream->identifier(
					$platform->quoteIdentifier($namespace))
					->text('.');
		}

		return $stream->identifier(
			$platform->quoteIdentifier($identifier->getLocalName()));
	}

	protected function tokenizeTableIdentifier(TokenStream $stream,
		TokenStreamContextInterface $context,
		TableStructure $tableStructure)
	{
		$platform = $context->getPlatform();
		$scoped = $platform->queryFeature(
			[
				K::FEATURE_INDEX,
				K::FEATURE_SCOPED
			], false);

		if ($scoped)
			return $stream->identifier(
				$platform->quoteIdentifier($tableStructure->getName()));

		return $stream->identifier(
			$platform->quoteIdentifierPath($tableStructure));
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