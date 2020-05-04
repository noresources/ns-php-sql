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
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureResolver;
use NoreSources\SQL\Structure\StructureResolverAwareInterface;
use NoreSources\SQL\Structure\StructureResolverAwareTrait;

/**
 * Statement building context data
 */
class StatementTokenStreamContext implements StatementInputDataInterface,
	StructureResolverAwareInterface, TokenStreamContextInterface
{
	use InputDataTrait;
	use OutputDataTrait;
	use StructureResolverAwareTrait;

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
		$this->setStructureResolver(new StructureResolver($pivot));
		$this->resultColumnAliases = new Stack();
	}

	public function getStatementBuilder()
	{
		return $this->builder;
	}

	public function setResultColumn($index, $data, $as = null)
	{
		if ($this->resultColumnAliases->count() > 1)
			return;

		$this->resultColumns->setColumn($index, $data, $as);
	}

	public function setStatementType($type)
	{
		if ($this->resultColumnAliases->count() > 1)
			return;
		$this->statementType = $type;
	}

	public function findColumn($path)
	{
		if ($this->resultColumnAliases->isEmpty())
			$this->resultColumnAliases->push(new \ArrayObject());

		if ($this->resultColumnAliases->offsetExists($path))
			return $this->resultColumnAliases->offsetGet($path);

		return $this->structureResolver->findColumn($path);
	}

	public function setAlias($alias, $reference)
	{
		if ($reference instanceof StructureElementInterface)
		{
			return $this->structureResolver->setAlias($alias, $reference);
		}
		else
		{
			if ($this->resultColumnAliases->isEmpty())
				$this->resultColumnAliases->push(new \ArrayObject());

			$this->resultColumnAliases->offsetSet($alias, $reference);
		}
	}

	public function isAlias($identifier)
	{
		if ($this->resultColumnAliases->count())
		{
			if ($this->resultColumnAliases->offsetExists($identifier))
				return true;
		}

		return $this->structureResolver->isAlias($identifier);
	}

	public function pushResolverContext(StructureElementInterface $pivot = null)
	{
		$this->resultColumnAliases->push(new \ArrayObject());
		$this->structureResolver->pushResolverContext($pivot);
	}

	public function popResolverContext()
	{
		$this->resultColumnAliases->pop();
		$this->structureResolver->popResolverContext();
	}

	/**
	 *
	 * @var \Noresources\Stack Stack of \ArrayObject
	 */
	private $resultColumnAliases;

	/**
	 *
	 * @var StatementBuilderInterface
	 */
	private $builder;
}