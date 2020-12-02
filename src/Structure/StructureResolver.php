<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\Container;
use NoreSources\Stack;

/**
 * Reference implementation of StructureResolverInterface
 */
class StructureResolver implements StructureResolverInterface
{

	/**
	 *
	 * @param StructureElementInterface $pivot
	 *        	Reference element
	 */
	public function __construct(StructureElementInterface $pivot = null)
	{
		$this->contextStack = new Stack();

		if ($pivot instanceof StructureElementInterface)
			$this->setPivot($pivot);
	}

	public function __get($member)
	{
		if (!$this->contextStack->isEmpty())
			return $this->contextStack->$member;

		throw new \RuntimeException(
			'Context stack not initialized (get ' . $member . ')');
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

	public function setPivot(StructureElementInterface $pivot)
	{
		if ($this->contextStack->isEmpty())
			$this->contextStack->push(
				new StructureResolverContext($pivot));
		else
			$this->pivot = $pivot;
	}

	public function getPivot()
	{
		if (!$this->contextStack->isEmpty())
			return $this->contextStack->pivot;
		return null;
	}

	public function findColumn($path)
	{
		$path = StructureElementIdentifier::make($path);
		$structureKey = ColumnStructure::class;
		if (($cached = Container::keyValue($this->cache[$structureKey],
			$path->getPath())))
			return $cached;

		$x = $path->getArrayCopy();
		$c = \count($x);
		$name = $x[$c - 1];

		$table = null;

		if ($c == 1)
			$table = $this->getDefaultTable();
		elseif ($c == 2)
			$table = $this->findTable($x[0]);
		elseif ($c == 3)
		{
			$namespace = $this->findNamespace($x[0]);
			if ($namespace)
				$table = $namespace->offsetGet($x[1]);
		}

		if (!($table instanceof TableStructure))
			return null;

		$column = $table->offsetGet($name);

		if ($column instanceof ColumnStructure)
		{
			$key = ColumnStructure::class;
			$this->cache[$key]->offsetSet($path->getPath(), $column);
		}
		else
			throw new StructureResolverException($path->getPath(),
				'column');

		return $column;
	}

	public function findTable($path)
	{
		$path = StructureElementIdentifier::make($path);
		$cached = null;

		if (($cached = Container::keyValue(
			$this->cache[TableStructure::class], $path->getPath())))
			return $cached;

		if (($cached = Container::keyValue(
			$this->cache[ViewStructure::class], $path->getPath())))
			return $cached;

		$x = $path->getArrayCopy();
		$c = \count($x);
		$name = $x[$c - 1];

		$namespace = null;

		if ($c == 1)
			$namespace = $this->getDefaultNamespace();
		elseif ($c == 2)
			$namespace = $this->findNamespace($x[0]);

		$table = ($namespace instanceof NamespaceStructure) ? $namespace->offsetGet(
			$name) : null;

		if (($table instanceof TableStructure) ||
			($table instanceof ViewStructure))
		{
			$key = \get_class($table);
			$this->cache[$key]->offsetSet($path->getPath(), $table);
		}
		else
			throw new StructureResolverException($path->getPath(),
				'table');

		return $table;
	}

	public function findNamespace($path)
	{
		$path = StructureElementIdentifier::make($path);
		if ($this->cache[NamespaceStructure::class]->offsetExists(
			$path->getPath()))
			return $this->cache[NamespaceStructure::class][$path->getPath()];

		$datasource = $this->pivot;
		while ($datasource &&
			!($datasource instanceof DatasourceStructure))
			$datasource = $datasource->getParentElement();

		$namespace = ($datasource instanceof DatasourceStructure) ? $datasource->offsetGet(
			$path->getPath()) : null;

		if ($namespace instanceof NamespaceStructure)
		{
			$key = NamespaceStructure::class;
			$this->cache[$key]->offsetSet($path->getPath(), $namespace);
		}
		else
			throw new StructureResolverException($path->getPath(),
				'namespace');

		return $namespace;
	}

	public function setAlias($alias, $reference)
	{
		$key = get_class($reference);
		$this->cache[$key]->offsetSet($alias, $reference);
		$this->aliases->offsetSet($alias, $reference);
	}

	public function isAlias($identifier)
	{
		return $this->aliases->offsetExists($identifier);
	}

	public function setTemporaryTable($name,
		ColumnDescriptionMapInterface $columns)
	{
		$table = new TableStructure($name);
		foreach ($columns->getColumnIterator() as $n => $c)
		{
			/**
			 *
			 * @var ColumnDescriptionInterface $c
			 */

			$column = new ColumnStructure($n, $table);
			foreach ($c as $key => $value)
			{
				$column->setColumnProperty($key, $value);
			}

			$table->appendElement($column);
			$this->setAlias($n, $column);
		}

		$this->setAlias($name, $table);
	}

	public function pushResolverContext(
		StructureElementInterface $pivot)
	{
		$this->contextStack->push(
			new StructureResolverContext(
				($pivot instanceof StructureElementInterface) ? $pivot : $pivot));
	}

	public function popResolverContext()
	{
		return $this->contextStack->pop();
	}

	/**
	 *
	 * @throws StructureResolverException
	 * @return NamespaceStructure
	 */
	protected function getDefaultNamespace()
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
		elseif ($this->pivot instanceof TableStructure ||
			$this->pivot instanceof ViewStructure)
			return $this->pivot->getParentElement();
		elseif ($this->pivot instanceof ColumnStructure)
			return $this->pivot->getParentElement(2);

		throw new StructureResolverException('Default namespace');
	}

	/**
	 *
	 * @throws StructureResolverException
	 * @return TableStructure
	 */
	protected function getDefaultTable()
	{
		if ($this->pivot instanceof ColumnStructure)
			return $this->pivot->getParentElement();
		elseif ($this->pivot instanceof TableStructure)
			return $this->pivot;
		else
		{
			$namespace = $this->getDefaultNamespace();

			$tables = Container::filter($namespace,
				function ($name, $e) {
					return ($e instanceof TableStructure);
				});

			if (Container::count($tables) == 1)
				return Container::firstValue($tables);
		}

		throw new StructureResolverException('Default table');
	}

	/**
	 *
	 * @var \NoreSources\Stack
	 */
	private $contextStack;
}
