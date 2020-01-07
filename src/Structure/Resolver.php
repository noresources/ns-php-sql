<?php

/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\SQL\Constants as K;
use NoreSources\Stack;

class StructureResolverException extends \Exception
{

	public function __construct($path)
	{
		parent::__construct($path . ' not found');
	}
}

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
		$this->cache = new \ArrayObject([
			TableColumnStructure::class => new \ArrayObject(),
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
			$this->cache[get_class($p)]->offsetSet($p->getName(), $p);
			$p = $p->parent();
		}
	}
}

class StructureResolver
{

	/**
	 *
	 * @param StructureElement $pivot
	 *        	Reference element
	 */
	public function __construct(StructureElement $pivot = null)
	{
		$this->contextStack = new Stack();

		if ($pivot instanceof StructureElement)
		{
			$this->contextStack->push(new StructureResolverContext($pivot));
		}
	}

	public function __get($member)
	{
		if (!$this->contextStack->isEmpty())
			return $this->contextStack->$member;

		throw new \RuntimeException('Context stack not initialized');
	}

	public function __set($member, $value)
	{
		if (!$this->contextStack->isEmpty())
		{
			$this->contextStack->$member = $value;
			return;
		}

		throw new \RuntimeException('Context stack not initialized');
	}

	/**
	 * Define the reference node and reset cache
	 *
	 * @param StructureElement $pivot
	 */
	public function setPivot(StructureElement $pivot)
	{
		if ($this->contextStack->isEmpty())
			$this->contextStack->push(new StructureResolverContext($pivot));

		$this->pivot = $pivot;
	}

	/**
	 *
	 * @return \NoreSources\SQL\StructureElement
	 */
	public function getPivot()
	{
		return $this->pivot;
	}

	/**
	 *
	 * @param string $path
	 * @throws StructureResolverException
	 * @return \NoreSources\SQL\TableColumnStructure
	 */
	public function findColumn($path)
	{
		if ($this->cache[TableColumnStructure::class]->offsetExists($path))
		{
			return $this->cache[TableColumnStructure::class][
				$path
			];
		}

		$x = explode('.', $path);
		$c = count($x);
		$name = $x[$c - 1];

		$table = null;

		if ($c == 1)
		{
			$table = $this->getDefaultTable();
		}
		elseif ($c == 2)
		{
			$table = $this->findTable($x[0]);
		}
		elseif ($c == 3)
		{
			$tableset = $this->findTableset($x[0]);
			if ($tableset)
			{
				$table = $tableset->offsetGet($x[1]);
			}
		}

		if (!($table instanceof TableStructure))
			return null;

		$column = $table->offsetGet($name);

		if ($column instanceof TableColumnStructure)
		{
			$this->cache[TableColumnStructure::class]->offsetSet($path, $column);
		}
		else
		{
			throw new StructureResolverException($path);
		}

		return $column;
	}

	/**
	 *
	 * @param string $path
	 * @throws StructureResolverException
	 * @return \NoreSources\SQL\TableStructure
	 */
	public function findTable($path)
	{
		if ($this->cache[TableStructure::class]->offsetExists($path))
		{
			return $this->cache[TableStructure::class][
				$path
			];
		}

		$x = explode('.', $path);
		$c = count($x);
		$name = $x[$c - 1];

		$tableset = null;

		if ($c == 1)
		{
			$tableset = $this->getDefaultTableset();
		}
		else 
			if ($c == 2)
			{
				$tableset = $this->findTableset($x[0]);
			}

		$table = ($tableset instanceof TablesetStructure) ? $tableset->offsetGet($name) : null;

		if ($table instanceof TableStructure)
		{
			$this->cache[TableStructure::class]->offsetSet($path, $table);
		}
		else
		{
			throw new StructureResolverException($path);
		}

		return $table;
	}

	/**
	 *
	 * @param string $path
	 * @throws StructureResolverException
	 * @return \NoreSources\SQL\TablesetStructure
	 */
	public function findTableset($path)
	{
		if ($this->cache[TablesetStructure::class]->offsetExists($path))
		{
			return $this->cache[TablesetStructure::class][
				$path
			];
		}

		$datasource = $this->pivot;
		while ($datasource && !($datasource instanceof DatasourceStructure))
		{
			$datasource = $datasource->parent();
		}

		$tableset = ($datasource instanceof DatasourceStructure) ? $datasource->offsetGet($path) : null;

		if ($tableset instanceof TablesetStructure)
		{
			$this->cache[TablesetStructure::class]->offsetSet($path, $tableset);
		}
		else
		{
			throw new StructureResolverException($path);
		}
		return $tableset;
	}

	/**
	 *
	 * @param string $alias
	 * @param StructureElement $structure
	 */
	public function setAlias($alias, StructureElement $reference)
	{
		$this->cache[get_class($reference)]->offsetSet($alias, $reference);
		$this->aliases->offsetSet($alias, $reference);
	}

	public function isAlias($identifier)
	{
		return $this->aliases->offsetExists($identifier);
	}

	public function pushResolverContext(StructureElement $pivot = null)
	{
		$this->contextStack->push(
			new StructureResolverContext(($pivot instanceof StructureElement) ? $pivot : $pivot));
	}

	public function popResolverContext()
	{
		return $this->contextStack->pop();
	}

	private function getDefaultTableset()
	{
		if ($this->pivot instanceof DatasourceStructure)
		{
			if ($this->pivot->count() == 1)
			{
				return $this->pivot->getIterator()->current();
			}
		}
		elseif ($this->pivot instanceof TablesetStructure)
			return $this->pivot;
		elseif ($this->pivot instanceof TableStructure)
			return $this->pivot->parent();
		elseif ($this->pivot instanceof TableColumnStructure)
			return $this->pivot->parent(2);

		throw new StructureResolverException('Default tableset');
	}

	private function getDefaultTable()
	{
		if ($this->pivot instanceof TableColumnStructure)
		{
			return $this->pivot->parent();
		}
		elseif ($this->pivot instanceof TableStructure)
		{
			return $this->pivot;
		}
		else
		{
			$tableset = $this->getDefaultTableset();
			if ($tableset instanceof TablesetStructure && ($tableset->count() == 1))
				return $tableset->getIterator()->current();
		}

		throw new StructureResolverException('Default table');
	}

	/**
	 *
	 * @var \NoreSources\Stack
	 */
	private $contextStack;
}
