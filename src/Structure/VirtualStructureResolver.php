<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\Container\Container;

class VirtualStructureResolver extends StructureResolver implements
	StructureProviderInterface
{

	public function __construct(StructureElementInterface $pivot = null)
	{
		$this->temporaryStructure = new DatasourceStructure();
		parent::__construct($this->temporaryStructure);
	}

	public function setDefaultNamespace($name)
	{
		$this->defaultNamespaceName = $name;
	}

	public function getStructure()
	{
		return $this->temporaryStructure;
	}

	public function findColumn($path)
	{
		$path = Identifier::make($path);
		try
		{
			return parent::findColumn($path);
		}
		catch (StructureResolverException $e)
		{}

		$path = $path->getArrayCopy();

		$c = \count($path);

		if ($c == 1)
		{
			try
			{
				$table = $this->getDefaultTable();
				array_unshift($path, $table->getName());
				$c++;
			}
			catch (StructureResolverException $e)
			{
				return $this->addStructurePath(\array_reverse($path),
					[
						ColumnStructure::class
					]);
			}
		}

		if ($c == 2)
		{
			$namespace = $this->getDefaultNamespace();
			if ($namespace instanceof NamespaceStructure)
				array_unshift($path, $namespace->getName());
		}

		return $this->addStructurePath(\array_reverse($path),
			[
				ColumnStructure::class,
				TableStructure::class,
				NamespaceStructure::class
			]);
	}

	public function findTable($path)
	{
		$path = Identifier::make($path);
		try
		{
			return parent::findTable($path);
		}
		catch (StructureResolverException $e)
		{}

		$path = $path->getArrayCopy();
		$c = \count($path);
		if ($c == 1)
		{
			$namespace = $this->getDefaultNamespace();
			if ($namespace instanceof NamespaceStructure)
				array_unshift($path, $namespace->getName());
		}

		return $this->addStructurePath(\array_reverse($path),
			[
				TableStructure::class,
				NamespaceStructure::class
			]);
	}

	public function findNamespace($path)
	{
		$path = Identifier::make($path);
		try
		{
			return parent::findNamespace($path);
		}
		catch (StructureResolverException $e)
		{}

		$path = $path->getArrayCopy();
		return $this->addStructurePath(\array_reverse($path),
			[
				NamespaceStructure::class
			]);
	}

	private function addStructurePath($reversePathParts, $classes)
	{
		$first = null;
		$current = null;
		$count = \count($reversePathParts);
		for ($i = 0; $i < $count; $i++)
		{
			$name = $reversePathParts[$i];
			$path = $name;
			for ($j = $i + 1; $j < $count; $j++)
				$path = $reversePathParts[$j] . '.' . $path;

			$className = $classes[$i];

			if (($cached = Container::keyValue($this->cache[$className],
				$path)))
			{
				if ($current &&
					$cached instanceof StructureElementContainerInterface)
					$cached->appendElement($current);
				$current = $cached;
			}
			else
			{
				$s = (new \ReflectionClass($className))->newInstance(
					$name);

				if ($s instanceof StructureElementContainerInterface &&
					$current)
					$s->appendElement($current);
				$current = $s;
				$this->cache[$className]->offsetSet($path, $current);
			}

			if ($first == null)
				$first = $current;
		}

		if ($current && !($current instanceof ColumnStructure))
			$this->temporaryStructure->appendElement($current);

		return $first;
	}

	public function getDefaultNamespace()
	{
		if (isset($this->defaultNamespaceName))
		{
			return $this->findNamespace($this->defaultNamespaceName);
		}

		try
		{
			return parent::getDefaultNamespace();
		}
		catch (StructureResolverException $e)
		{}

		return $this->getStructure();
	}

	/**
	 *
	 * @var string
	 */
	private $defaultNamespaceName;

	/**
	 *
	 * @var DatasourceStructure
	 */
	private $temporaryStructure;
}
