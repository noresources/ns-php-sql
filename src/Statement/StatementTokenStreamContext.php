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
use NoreSources\SQL\Expression\TokenStreamContext;
use NoreSources\SQL\Structure\StructureElement;
use NoreSources\SQL\Structure\StructureResolver;
use NoreSources\SQL\Structure\StructureResolverAwareInterface;
use NoreSources\SQL\Structure\StructureResolverAwareTrait;

/**
 * Statement building context data
 */
class StatementTokenStreamContext implements InputData, StructureResolverAwareInterface, TokenStreamContext
{
	use InputDataTrait;
	use OutputDataTrait;
	use StructureResolverAwareTrait;

	/**
	 *
	 * @param StatementBuilderInterface $builder
	 * @param StructureElement $pivot
	 */
	public function __construct(StatementBuilderInterface $builder, StructureElement $pivot = null)
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
		if ($reference instanceof StructureElement)
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

	public function pushResolverContext(StructureElement $pivot = null)
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