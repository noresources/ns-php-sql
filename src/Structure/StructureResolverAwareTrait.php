<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

/**
 * Implements StructureResolverAwareInterface and wraps
 * wrapping StructureResolverInterface methods to the internal StructureResolverInterface instance.
 */
trait StructureResolverAwareTrait
{

	/**
	 * Set the internal StructureResolverInterface instance
	 *
	 * @param StructureResolverInterface $resolver
	 */
	public function setStructureResolver(StructureResolverInterface $resolver)
	{
		$this->structureResolver = $resolver;
	}

	public function getStructureResolver()
	{
		return $this->structureResolver;
	}

	public function setPivot(StructureElement $pivot)
	{
		return $this->structureResolver->setPivot($pivot);
	}

	public function getPivot()
	{
		return $this->structureResolver->getPivot();
	}

	public function findColumn($path)
	{
		return $this->structureResolver->findColumn($path);
	}

	public function findTable($path)
	{
		return $this->structureResolver->findTable($path);
	}

	public function findTableset($path)
	{
		return $this->structureResolver->findTableset($path);
	}

	public function setAlias($alias, StructureElement $reference)
	{
		return $this->structureResolver->setAlias($alias, $reference);
	}

	public function isAlias($identifier)
	{
		return $this->structureResolver->isAlias($identifier);
	}

	public function pushResolverContext(StructureElement $pivot = null)
	{
		return $this->structureResolver->pushResolverContext($pivot);
	}

	public function popResolverContext()
	{
		return $this->structureResolver->popResolverContext();
	}

	/**
	 *
	 * @var StructureResolver
	 */
	private $structureResolver;
}