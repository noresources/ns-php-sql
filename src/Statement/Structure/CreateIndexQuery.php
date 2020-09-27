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

use NoreSources\TypeConversion;
use NoreSources\TypeDescription;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\StructureElementIdentifier;
use NoreSources\SQL\Expression\TableReference;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Expression\TokenizableExpressionInterface;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Statement\StatementException;
use NoreSources\SQL\Statement\Traits\WhereConstraintTrait;
use NoreSources\SQL\Structure\IndexStructure;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\TableStructure;

/**
 * CREATE INDEX statement
 *
 * @see https://www.sqlite.org/lang_createindex.html
 */
class CreateIndexQuery extends Statement
{
	use WhereConstraintTrait;

	const UNIQUE = 0x01;

	public function __construct($identifier = null)
	{
		$this->indexTable = null;
		$this->indexFlags = 0;
		$this->initializeWhereConstraints();
		$this->indexColumns = [];

		if ($identifier !== null)
			$this->identifier($identifier);
	}

	public function setFromIndexStructure(IndexStructure $index)
	{
		$this->table($index->getIndexTable());
		$this->identifier($index);
		$this->indexColumns = [];
		foreach ($index->getIndexColumns() as $column)
		{
			$this->indexColumns[] = $column->getName();
		}
		$this->indexFlags = $index->getIndexFlags();
	}

	/**
	 *
	 * @param StructureElementIdentifier|IndexStructure|string $identifier
	 *        	Index identifier
	 *
	 * @return CreateIndexQuery
	 */
	public function identifier($identifier)
	{
		if ($identifier instanceof IndexStructure)
			$identifier = $identifier->getPath();

		if ($identifier instanceof StructureElementIdentifier)
			$this->indexIdentifier = $identifier;
		else
			$this->indexIdentifier = new StructureElementIdentifier(
				\strval($identifier));

		return $this;
	}

	/**
	 *
	 * @param TableReference|TableStructure|string $table
	 *        	Table reference
	 *
	 * @return CreateIndexQuery
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

	/**
	 *
	 * @param array $args...
	 *        	Column names
	 * @return \NoreSources\SQL\Statement\Structure\CreateIndexQuery
	 */
	public function columns()
	{
		$c = func_num_args();
		for ($i = 0; $i < $c; $i++)
		{
			$column = func_get_arg($i);
			if ($column instanceof TokenizableExpressionInterface)
				$this->indexColumns[] = $column;
			elseif (TypeDescription::hasStringRepresentation($column))
				$this->indexColumns[] = TypeConversion::toString(
					$column);
		}

		return $this;
	}

	/**
	 *
	 * @param integer $flags
	 * @return \NoreSources\SQL\Statement\CreateIndexQuery
	 */
	public function flags($flags)
	{
		$this->indexFlags = $flags;
		return $this;
	}

	/**
	 * WHERE constraints
	 *
	 * @param Evaluable $args...
	 *        	List of Evaluable expressions
	 *
	 * @return CreateIndexQuery
	 */
	public function where()
	{
		return $this->addConstraints($this->whereConstraints,
			func_get_args());
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();
		$scoped = $platform->queryFeature(
			[
				K::PLATFORM_FEATURE_INDEX,
				K::PLATFORM_FEATURE_SCOPED
			], false);

		$existsCondition = $platform->queryFeature(
			[
				K::PLATFORM_FEATURE_CREATE,
				K::PLATFORM_FEATURE_INDEX,
				K::PLATFORM_FEATURE_EXISTS_CONDITION
			], false);

		$context->setStatementType(K::QUERY_CREATE_INDEX);

		$tableStructure = $context->findTable($this->indexTable->path);
		$context->pushResolverContext($tableStructure);

		/**
		 *
		 * @var TableStructure $tableStructure
		 */

		$stream->keyword('create');
		if ($this->indexFlags & self::UNIQUE)
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
					if ($stream instanceof IndexStructure)
						$structure = $structure->getParentElement();

					if ($structure instanceof NamespaceStructure)
						$stream->identifier(
							$context->getPlatform()
								->quoteIdentifierPath($structure))
							->text('.');

					$stream->identifier(
						$context->getPlatform()
							->quoteIdentifier(
							$this->indexIdentifier->path));
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
		foreach ($this->indexColumns as $column)
		{
			if ($i++)
				$stream->text(',');

			if ($column instanceof TokenizableExpressionInterface)
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
	 * @var integer
	 */
	private $indexFlags;

	/**
	 *
	 * @var string
	 */
	private $indexIdentifier;

	/**
	 *
	 * @var \NoreSources\SQL\Expression\TableReference
	 */
	private $indexTable;

	/**
	 *
	 * @var Evaluable[] $indexColumns;
	 */
	private $indexColumns;
}