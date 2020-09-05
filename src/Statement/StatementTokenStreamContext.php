<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Statement;

use NoreSources\Stack;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Structure\ColumnDescriptionMapInterface;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureResolver;
use NoreSources\SQL\Structure\StructureResolverProviderInterface;
use NoreSources\SQL\Structure\StructureResolverProviderTrait;

class StatementElementDMap implements StatementOutputDataInterface
{

	use OutputDataTrait;

	/**
	 *
	 * @var \ArrayObject
	 */
	public $aliases;

	/**
	 *
	 * @var \ArrayObject
	 */
	public $temporaryTables;

	public function __construct()
	{
		$this->initializeOutputData();
		$this->aliases = new \ArrayObject();
		$this->temporaryTables = new \ArrayObject();
	}
}

/**
 * Statement building context data
 */
class StatementTokenStreamContext implements StatementInputDataInterface,
	StructureResolverProviderInterface, TokenStreamContextInterface
{
	use InputDataTrait;
	use OutputDataTrait;
	use StructureResolverProviderTrait;

	/**
	 *
	 * @param StatementBuilderInterface $builder
	 * @param StructureElementInterface $pivot
	 */
	public function __construct(StatementBuilderInterface $builder,
		StructureElementInterface $pivot = null)
	{
		$this->initializeInputData(null);
		$this->initializeOutputData(null);
		$this->builder = $builder;
		$this->statementElements = new Stack();

		$this->setStructureResolver(new StructureResolver());

		if ($pivot instanceof StructureElementInterface)
			$this->pushResolverContext($pivot);
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Statement\StatementBuilderProviderInterface::getStatementBuilder()
	 */
	public function getStatementBuilder()
	{
		return $this->builder;
	}

	public function setResultColumn($index, $data, $as = null)
	{
		if ($this->statementElements->count() > 1)
		{
			$this->statementElements->getResultColumns()->setColumn($index, $data, $as);
			return;
		}

		$this->resultColumns->setColumn($index, $data, $as);
	}

	public function setStatementType($type)
	{
		if ($this->statementElements->count() > 1)
		{
			$this->statementElements->setStatementType($type);
			return;
		}

		$this->statementType = $type;
	}

	public function findColumn($path)
	{
		if ($this->statementElements->isEmpty())
			$this->statementElements->push(new StatementElementDMap());

		if ($this->statementElements->aliases->offsetExists($path))
			return $this->statementElements->aliases->offsetGet($path);

		return $this->structureResolver->findColumn($path);
	}

	public function setAlias($alias, $reference)
	{
		if ($reference instanceof StructureElementInterface)
			return $this->structureResolver->setAlias($alias, $reference);
		else
		{
			if ($this->statementElements->isEmpty())
				$this->statementElements->push(new StatementElementDMap());

			$this->statementElements->aliases->offsetSet($alias, $reference);
		}
	}

	public function isAlias($identifier)
	{
		if ($this->statementElements->count())
		{
			if ($this->statementElements->aliases->offsetExists($identifier))
				return true;
		}

		return $this->structureResolver->isAlias($identifier);
	}

	public function setTemporaryTable($name, ColumnDescriptionMapInterface $columns)
	{
		$this->getStructureResolver()->setTemporaryTable($name, $columns);
	}

	public function pushResolverContext(StructureElementInterface $pivot)
	{
		$this->statementElements->push(new StatementElementDMap());
		$this->structureResolver->pushResolverContext($pivot);
	}

	public function popResolverContext()
	{
		/**
		 *
		 * @todo get result columns and data row container reference from current context
		 *       and put them in the list of "available columns and table"
		 */
		$this->statementElements->pop();
		$this->structureResolver->popResolverContext();
	}

	/**
	 *
	 * @var \Noresources\Stack Stack of \ArrayObject
	 */
	private $statementElements;

	/**
	 *
	 * @var StatementBuilderInterface
	 */
	private $builder;
}