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

class StructureResolver implements StructureResolverInterface
{

	/**
	 *
	 * @param StructureElement $pivot
	 *        	Reference element
	 */
	public function __construct(StructureElement $pivot = null)
	{
		$this->contextStack = new \NoreSources\Stack();

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

	public function setPivot(StructureElement $pivot)
	{
		if ($this->contextStack->isEmpty())
			$this->contextStack->push(new StructureResolverContext($pivot));

		$this->pivot = $pivot;
	}

	public function getPivot()
	{
		return $this->pivot;
	}

	public function findColumn($path)
	{
		if ($this->cache[ColumnStructure::class]->offsetExists($path))
		{
			return $this->cache[ColumnStructure::class][$path];
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
			$namespace = $this->findNamespace($x[0]);
			if ($namespace)
			{
				$table = $namespace->offsetGet($x[1]);
			}
		}

		if (!($table instanceof TableStructure))
			return null;

		$column = $table->offsetGet($name);

		if ($column instanceof ColumnStructure)
		{
			$key = ColumnStructure::class;
			$this->cache[$key]->offsetSet($path, $column);
		}
		else
			throw new StructureResolverException($path, 'column');

		return $column;
	}

	public function findTable($path)
	{
		if ($this->cache[TableStructure::class]->offsetExists($path))
		{
			return $this->cache[TableStructure::class][$path];
		}

		$x = explode('.', $path);
		$c = count($x);
		$name = $x[$c - 1];

		$namespace = null;

		if ($c == 1)
		{
			$namespace = $this->getDefaultNamespace();
		}
		elseif ($c == 2)
		{
			$namespace = $this->findNamespace($x[0]);
		}

		$table = ($namespace instanceof NamespaceStructure) ? $namespace->offsetGet($name) : null;

		if ($table instanceof TableStructure)
		{
			$key = TableStructure::class;
			$this->cache[$key]->offsetSet($path, $table);
		}
		else
			throw new StructureResolverException($path, 'table');

		return $table;
	}

	public function findNamespace($path)
	{
		if ($this->cache[NamespaceStructure::class]->offsetExists($path))
		{
			return $this->cache[NamespaceStructure::class][$path];
		}

		$datasource = $this->pivot;
		while ($datasource && !($datasource instanceof DatasourceStructure))
		{
			$datasource = $datasource->getParent();
		}

		$namespace = ($datasource instanceof DatasourceStructure) ? $datasource->offsetGet($path) : null;

		if ($namespace instanceof NamespaceStructure)
		{
			$key = NamespaceStructure::class;
			$this->cache[$key]->offsetSet($path, $namespace);
		}
		else
			throw new StructureResolverException($path, 'namespace');

		return $namespace;
	}

	public function setAlias($alias, StructureElement $reference)
	{
		$key = get_class($reference);
		$this->cache[$key]->offsetSet($alias, $reference);
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

	private function getDefaultNamespace()
	{
		if ($this->pivot instanceof DatasourceStructure)
		{
			if ($this->pivot->count() == 1)
			{
				return $this->pivot->getIterator()->current();
			}
		}
		elseif ($this->pivot instanceof NamespaceStructure)
			return $this->pivot;
		elseif ($this->pivot instanceof TableStructure)
			return $this->pivot->getParent();
		elseif ($this->pivot instanceof ColumnStructure)
			return $this->pivot->getParent(2);

		throw new StructureResolverException('Default namespace');
	}

	private function getDefaultTable()
	{
		if ($this->pivot instanceof ColumnStructure)
		{
			return $this->pivot->getParent();
		}
		elseif ($this->pivot instanceof TableStructure)
		{
			return $this->pivot;
		}
		else
		{
			$namespace = $this->getDefaultNamespace();
			if ($namespace instanceof NamespaceStructure && ($namespace->count() == 1))
				return $namespace->getIterator()->current();
		}

		throw new StructureResolverException('Default table');
	}

	/**
	 *
	 * @var \NoreSources\Stack
	 */
	private $contextStack;
}
