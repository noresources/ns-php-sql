<?php

/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\SQL\Constants as K;

class StructureResolverException extends \Exception
{

	public function __construct($path)
	{
		parent::__construct($path . ' not found');
	}
}

class StructureResolver
{

	/**
	 * @param StructureElement $pivot Reference element
	 */
	public function __construct(StructureElement $pivot = null)
	{
		$this->cache = new \ArrayObject(array (
				'aliases' => new \ArrayObject(),
				'columns' => new \ArrayObject(),
				'tables' => new \ArrayObject(),
				'tablesets' => new \ArrayObject(),
				'datasource' => new \ArrayObject()
		));

		$this->structureAliases = new \ArrayObject();

		if ($pivot instanceof StructureElement)
		{
			$this->setPivot($pivot);
		}
	}

	/**
	 * Define the reference node and reset cache
	 * @param StructureElement $pivot
	 */
	public function setPivot(StructureElement $pivot)
	{
		foreach ($this->cache as $key => &$table)
		{
			$table->exchangeArray(array ());
		}

		$this->pivot = $pivot;
		$key = self::getKey($pivot);
		$this->cache[$key]->offsetSet($pivot->getName(), $pivot);
		$this->cache[$key]->offsetSet($pivot->getPath(), $pivot);
		$p = $pivot->parent();
		while ($p instanceof StructureElement)
		{
			$this->cache[self::getKey($p)]->offsetSet($p->getName(), $p);
			$p = $p->parent();
		}
	}

	/**
	 * @return \NoreSources\SQL\StructureElement
	 */
	public function getPivot()
	{
		return $this->pivot;
	}

	/**
	 * @param string $path
	 * @throws StructureResolverException
	 * @return \NoreSources\SQL\TableColumnStructure
	 */
	public function findColumn($path)
	{
		if ($this->cache['columns']->offsetExists($path))
		{
			return $this->cache['columns'][$path];
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
			$this->cache['columns']->offsetSet($path, $column);
		}
		else
		{
			throw new StructureResolverException($path);
		}

		return $column;
	}

	/**
	 * @param string $path
	 * @throws StructureResolverException
	 * @return \NoreSources\SQL\TableStructure
	 */
	public function findTable($path)
	{
		if ($this->cache['tables']->offsetExists($path))
		{
			return $this->cache['tables'][$path];
		}

		$x = explode('.', $path);
		$c = count($x);
		$name = $x[$c - 1];

		$tableset = null;

		if ($c == 1)
		{
			$tableset = $this->getDefaultTableset();
		}
		else if ($c == 2)
		{
			$tableset = $this->findTableset($x[0]);
		}

		$table = ($tableset instanceof TableSetStructure) ? $tableset->offsetGet($name) : null;

		if ($table instanceof TableStructure)
		{
			$this->cache['tables']->offsetSet($path, $table);
		}
		else
		{
			throw new StructureResolverException($path);
		}

		return $table;
	}

	/**
	 * @param string $path
	 * @throws StructureResolverException
	 * @return \NoreSources\SQL\TableSetStructure
	 */
	public function findTableset($path)
	{
		if ($this->cache['tablesets']->offsetExists($path))
		{
			return $this->cache['tablesets'][$path];
		}

		$datasource = $this->pivot;
		while ($datasource && !($datasource instanceof DatasourceStructure))
		{
			$datasource = $datasource->parent();
		}

		$tableset = ($datasource instanceof DatasourceStructure) ? $datasource->offsetGet($path) : null;

		if ($tableset instanceof TableSetStructure)
		{
			$this->cache['tablesets']->offsetSet($path, $tableset);
		}
		else
		{
			throw new StructureResolverException($path);
		}
		return $tableset;
	}

	/**
	 * @param string $alias
	 * @param StructureElement $structure
	 */
	public function setAlias($alias, $reference)
	{
		$this->cache[self::getKey($reference)]->offsetSet($alias, $reference);
		$this->structureAliases->offsetSet($alias, $reference);
	}

	public function isAlias($identifier)
	{
		return $this->structureAliases->offsetExists($identifier);
	}

	private static function getKey($item)
	{
		if ($item instanceof TableColumnStructure)
		{
			return 'columns';
		}
		elseif ($item instanceof TableStructure)
		{
			return 'tables';
		}
		elseif ($item instanceof TableSetStructure)
		{
			return 'tablesets';
		}
		elseif ($item instanceof DatasourceStructure)
		{
			return 'datasource';
		}
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
		elseif ($this->pivot instanceof TableSetStructure)
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
			if ($tableset instanceof TableSetStructure && ($tableset->count() == 1))
				return $tableset->getIterator()->current();
		}

		throw new StructureResolverException('Default table');
	}

	/**
	 * @var StructureElement
	 */
	private $pivot;

	/**
	 * @var \ArrayObject
	 */
	private $cache;

	/**
	 * @var \ArrayObject
	 */
	private $structureAliases;
}
