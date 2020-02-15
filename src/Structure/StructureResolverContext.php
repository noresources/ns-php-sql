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

class StructureResolverContext
{
	
	/**
	 *
	 * @var StructureElement
	 */
	public $pivot;
	
	/**
	 *
	 * @var \ArrayObject
	 */
	public $cache;
	
	/**
	 *
	 * @var \ArrayObject
	 */
	public $aliases;
	
	public function __construct(StructureElement $pivot)
	{
		$this->pivot = $pivot;
		$this->cache = new \ArrayObject(
			[
				ColumnStructure::class => new \ArrayObject(),
				TableStructure::class => new \ArrayObject(),
				TablesetStructure::class => new \ArrayObject(),
				DatasourceStructure::class => new \ArrayObject()
			]);
		$this->aliases = new \ArrayObject();
		
		$key = get_class($pivot);
		$this->cache[$key]->offsetSet($pivot->getName(), $pivot);
		$this->cache[$key]->offsetSet($pivot->getPath(), $pivot);
		$p = $pivot->parent();
		while ($p instanceof StructureElement)
		{
			$key = get_class($p);
			$this->cache[$key]->offsetSet($p->getName(), $p);
			$p = $p->parent();
		}
	}
}
