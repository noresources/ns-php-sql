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
use NoreSources\SQL\Structure\StructureElementIdentifier;
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
	 * @param StructureElementIdentifier $identifier
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
	 * @param StructureElementIdentifier|string $identifier
	 *        	Index identifier
	 *
	 * @return $this
	 */
	public function identifier($identifier)
	{
		$this->indexIdentifier = StructureElementIdentifier::make(
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
			$stream->keyword('if')
				->space()
				->keyword('not')
				->space()
				->keyword('exists');

		if ($this->indexIdentifier instanceof StructureElementIdentifier)
		{
			$stream->space();

			if ($scoped)
			{
				$parts = $this->indexIdentifier->getPathParts();
				if (\count($parts) > 1)
					$stream->identifier(
						$context->getPlatform()
							->quoteIdentifierPath($parts));
				else // Last chance to find the element namespace
				{
					$structure = $context->getPivot();

					if ($structure instanceof NamespaceStructure)
						$stream->identifier(
							$context->getPlatform()
								->quoteIdentifierPath($structure))
							->text('.');

					$stream->identifier(
						$context->getPlatform()
							->quoteIdentifier(
							$this->indexIdentifier->getLocalName()));
				}
			}
			else
				$stream->identifier(
					$context->getPlatform()
						->quoteIdentifier(
						$this->indexIdentifier->getLocalName()));
		}

		$stream->space()
			->keyword('on')
			->space()
			->identifier(
			$context->getPlatform()
				->quoteIdentifierPath($tableStructure))
			->space()
			->text('(');

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

	/**
	 *
	 * @var StructureElementIdentifier
	 */
	private $indexIdentifier;

	/**
	 *
	 * @var \NoreSources\SQL\Syntax\TableReference
	 */
	private $indexTable;
}