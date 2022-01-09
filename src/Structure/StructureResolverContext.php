<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

/**
 * Internal Structure element resolver context
 */
class StructureResolverContext
{

	/**
	 *
	 * @var StructureElementInterface
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

	public function __construct(StructureElementInterface $pivot)
	{
		$this->pivot = $pivot;
		$this->cache = new \ArrayObject(
			[
				ColumnStructure::class => new \ArrayObject(),
				TableStructure::class => new \ArrayObject(),
				ViewStructure::class => new \ArrayObject(),
				NamespaceStructure::class => new \ArrayObject(),
				DatasourceStructure::class => new \ArrayObject()
			]);
		$this->aliases = new \ArrayObject();

		$key = get_class($pivot);
		$this->cache[$key]->offsetSet($pivot->getName(), $pivot);
		$this->cache[$key]->offsetSet(\strval($pivot->getIdentifier()),
			$pivot);
		$p = $pivot->getParentElement();
		while ($p instanceof StructureElementInterface)
		{
			$key = get_class($p);
			$this->cache[$key]->offsetSet($p->getName(), $p);
			$p = $p->getParentElement();
		}
	}
}
