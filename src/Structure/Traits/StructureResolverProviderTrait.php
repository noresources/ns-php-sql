<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Traits;

use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureResolver;
use NoreSources\SQL\Structure\StructureResolverInterface;

/**
 * Implements StructureResolverProviderInterface and wraps
 * wrapping StructureResolverInterface methods to the internal StructureResolverInterface instance.
 */
trait StructureResolverProviderTrait
{

	/**
	 * Set the internal StructureResolverInterface instance
	 *
	 * @param StructureResolverInterface $resolver
	 */
	public function setStructureResolver(
		StructureResolverInterface $resolver)
	{
		$this->structureResolver = $resolver;
	}

	public function getStructureResolver()
	{
		return $this->structureResolver;
	}

	public function setPivot(StructureElementInterface $pivot)
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

	public function findNamespace($path)
	{
		return $this->structureResolver->findNamespace($path);
	}

	public function setAlias($alias, $reference)
	{
		return $this->structureResolver->setAlias($alias, $reference);
	}

	public function isAlias($identifier)
	{
		return $this->structureResolver->isAlias($identifier);
	}

	public function pushResolverContext(
		StructureElementInterface $pivot = null)
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