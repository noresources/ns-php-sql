<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Inspector;

use NoreSources\SingletonTrait;
use NoreSources\Container\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\IndexDescriptionInterface;
use NoreSources\SQL\Structure\IndexStructure;
use NoreSources\SQL\Structure\KeyTableConstraintInterface;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\StructureElementContainerInterface;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureResolver;
use NoreSources\SQL\Structure\TableConstraintInterface;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\Type\TypeDescription;

class StructureInspector
{
	use SingletonTrait;

	/**
	 * Get the root StructureElementContainerInterface of the givven element
	 *
	 * @param StructureElementInterface $e
	 * @return StructureElementInterface
	 */
	public function getRootElement(StructureElementInterface $e)
	{
		$parent = $e;
		while ($parent->getParentElement())
			$parent = $parent->getParentElement();

		return $parent;
	}

	/**
	 *
	 * @param StructureElementInterface $element
	 * @return array<StructureElementInterface> Siblings of $element
	 */
	public function getSiblingElements(
		StructureElementInterface $element)
	{
		$parent = $element->getParentElement();
		$children = $parent->getChildElements();
		return Container::filter($children,
			function ($k, $e) use ($element) {
				return $e != $element;
			});
	}

	/**
	 *
	 * @param StructureElementInterface $p
	 * @param StructureElementInterface $c
	 * @return boolean TRUE if $p is an ancestor of $c
	 */
	public function isAncestorOf(StructureElementInterface $p,
		StructureElementInterface $c)
	{
		while ($c)
		{
			if ($c->getParentElement() == $p)
				return true;
			$c = $c->getParentElement();
		}
		return false;
	}

	/**
	 * Get the list of ancestor of a given element
	 *
	 * @param StructureElementInterface $e
	 * @return StructureElementInterface[] Array of ancestor from the most distant to the closest
	 */
	public function getAncestorTree(StructureElementInterface $e)
	{
		$ancestors = [];
		$p = $e->getParentElement();
		while ($p)
		{
			\array_unshift($ancestors, $p);
			$p = $p->getParentElement();
		}

		return $ancestors;
	}

	/**
	 * Get the closest command ancestor of two elements
	 *
	 * @param StructureElementInterface $a
	 * @param StructureElementInterface $b
	 * @return StructureElementInterface|NULL
	 */
	public function getCommonAncestor(StructureElementInterface $a,
		StructureElementInterface $b)
	{
		$aa = $this->getAncestorTree($a);
		$ab = $this->getAncestorTree($b);
		$common = null;

		while (\count($aa) && \count($ab))
		{
			$pa = \array_shift($aa);
			$pb = \array_shift($ab);
			if (!($pa && $pa == $pb))
				break;
			$common = $pa;
		}
		return $common;
	}

	/**
	 * Get a list of elements referred by the given element.
	 *
	 * @param StructureElementInterface $element
	 *        	Structure element
	 * @return StructureElementInterface[] Array of StructureELement referred by $element
	 */
	public function getReferences(StructureElementInterface $element)
	{
		$references = [];
		if ($element instanceof ForeignKeyTableConstraint)
		{
			$table = $element->getParentElement();
			$resolver = new StructureResolver(
				$table->getParentElement());
			$foreignTable = $resolver->findTable(
				$element->getForeignTable());

			$references[] = $foreignTable;
			foreach ($element->getColumns() as $c => $f)
			{
				$references[] = $foreignTable[$f];
			}
		}
		elseif ($element instanceof IndexDescriptionInterface)
		{
			$resolver = new StructureResolver(
				$element->getParentElement());
			$table = $element->getParentElement();
			foreach ($element->getColumns() as $c)
			{
				$references[] = $table[$c];
			}
		}
		elseif ($element instanceof TableStructure)
		{
			$foreignKeys = $element->getChildElements(
				ForeignKeyTableConstraint::class);
			foreach ($foreignKeys as $k)
			{
				$references = \array_merge($references,
					$this->getReferences($k));
			}
		}
		elseif ($element instanceof ColumnStructure)
		{
			/** @var TableStructure $table */
			$table = $element->getParentElement();
			foreach ($table->getConstraints() as $c)
			{
				if (($c instanceof ForeignKeyTableConstraint) &&
					$c->getColumns()->offsetExists($element->getName()))
				{
					$foreignColumnName = $c->getColumns()[$element->getName()];
					$resolver = new StructureResolver(
						$table->getParentElement());
					$foreignTable = $resolver->findTable(
						$c->getForeignTable());
					$references[] = $foreignTable[$foreignColumnName];
				}
			}
		}

		return $references;
	}

	public function getReverseReferenceMap(
		StructureElementInterface $root)
	{
		$list = [];
		$references = $this->getReferences($root);
		foreach ($references as $reference)
		{
			$path = $reference->getIdentifier()->getPath();
			if (!Container::keyExists($list, $path))
				$list[$path] = [];
			$list[$path][] = $root;
		}

		if ($root instanceof StructureElementContainerInterface)
		{
			foreach ($root as $element)
			{
				$l = $this->getReverseReferenceMap($element);
				foreach ($l as $path => $a)
				{
					if (!Container::keyExists($list, $path))
						$list[$path] = [];
					foreach ($a as $e)
						$list[$path][] = $e;
				}
			}
		}

		foreach ($list as $path => $a)
			$list[$path] = \array_unique($a, SORT_REGULAR);

		return $list;
	}

	public function getTableColumnConstraintFlags(
		ColumnStructure $column)
	{
		/** @var TableStructure $table */
		$table = $column->getParentElement();
		if (!($table instanceof TableStructure))
			return 0;

		$columnName = $column->getName();

		$flags = 0;
		foreach ($table->getConstraints() as $constraint)
		{
			if ($constraint instanceof KeyTableConstraintInterface)
			{
				if (Container::valueExists($constraint->getColumns(),
					$columnName))
					$flags |= $constraint->getConstraintFlags();
			}
			elseif ($constraint instanceof ForeignKeyTableConstraint)
			{
				if (Container::keyExists($constraint->getColumns(),
					$columnName))
					$flags |= $constraint->getConstraintFlags();
			}
		}

		$indexes = $table->getChildElements(IndexStructure::class);
		foreach ($indexes as $index)
		{
			/**
			 *
			 * @var IndexStructure $index
			 */
			if (Container::valueExists($index->getColumns(), $columnName))
				$flags |= K::CONSTRAINT_COLUMN_KEY;
		}

		$foreignReferences = $this->getReferences($column);
		if (\count($foreignReferences))
			$flags |= K::CONSTRAINT_COLUMN_FOREIGN_KEY;

		return $flags;
	}

	// /////////////////////////////////////////////////////////////

	/**
	 * Indicates if the given structure lement may contains persistent data
	 *
	 * @param StructureElementInterface|string $elementOrElementClassname
	 * @return boolean
	 */
	public function hasData($elementOrElementClassname)
	{
		$classname = $elementOrElementClassname;
		if (!\is_string($elementOrElementClassname))
			$classname = TypeDescription::getName(
				$elementOrElementClassname);
		return \in_array($classname,
			[
				ColumnStructure::class,
				TableStructure::class,
				NamespaceStructure::class
			]);
	}

	/**
	 * Indicates if the element is part of a table
	 *
	 * @param StructureElementInterface $element
	 *        	Element to check
	 * @return boolean TRUE if $element is a column or a table constraint. Note that IndexStructure
	 *         is not considered as a table component even if it is generally a parent of a table.
	 *
	 *
	 */
	public function isTableComponent(StructureElementInterface $element)
	{
		return ($element instanceof ColumnStructure) ||
			($element instanceof TableConstraintInterface);
	}

	/**
	 *
	 * @param StructureElementInterface $a
	 * @param StructureElementInterface $b
	 * @return boolean
	 */
	public function conflictsWith(StructureElementInterface $a,
		StructureElementInterface $b)
	{
		$sa = \strval($a->getIdentifier());
		$sb = \strval($b->getIdentifier());
		return \strcmp($sa, $sb) == 0;
	}

	/**
	 *
	 * @param StructureElementInterface $a
	 * @param StructureElementInterface $b
	 * @return boolean TRUE if $a depends on $b
	 */
	public function dependsOn(StructureElementInterface $a,
		StructureElementInterface $b)
	{
		if ($this->isAncestorOf($b, $a))
			return true;

		$resolver = new StructureResolver();
		$pa = $a->getParentElement();

		if (($a instanceof IndexDescriptionInterface) &&
			$pa instanceof TableStructure &&
			$b instanceof ColumnStructure &&
			$b->getParentElement() === $pa)
		{
			return Container::valueExists($a->getColumns(),
				$b->getName());
		}

		if ($a instanceof ColumnStructure &&
			$pa instanceof TableStructure)
		{
			$resolver->setPivot($a);
			$fks = $pa->getChildElements(
				ForeignKeyTableConstraint::class);
			foreach ($fks as $fk)
			{
				/**
				 *
				 * @var ForeignKeyTableConstraint $fk
				 */
				$fkt = $resolver->findTable($fk->getForeignTable());

				foreach ($fk->getColumns() as $c => $f)
				{
					if ($c != $a->getName())
						continue;

					$fc = $fkt[$f];
					if ($fc == $b || $this->dependsOn($fc, $b))
						return true;
				}
			}
		}
		elseif ($a instanceof TableStructure)
		{
			$resolver->setPivot($a);
			$fks = $a->getChildElements(
				ForeignKeyTableConstraint::class);
			foreach ($fks as $fk)
			{
				/**
				 *
				 * @var ForeignKeyTableConstraint $fk
				 */
				$fkt = $resolver->findTable($fk->getForeignTable());
				if ($fkt == $b || $this->dependsOn($fkt, $b))
					return true;
			}
		}
		elseif ($a instanceof ForeignKeyTableConstraint &&
			$a->getParentElement())
		{
			$resolver->setPivot($a->getParentElement());
			$fkt = $resolver->findTable($a->getForeignTable());
			if ($fkt == $b || $this->dependsOn($fkt, $b))
				return true;
		}

		return false;
	}

	/**
	 * Sort eleent by their mutual dependency
	 *
	 * @param StructureElementInterface $a
	 * @param StructureElementInterface $b
	 * @return number <ul><li>> 1 if $a depends on $b</li>
	 *         <li> &lt; 1 if $b depends on $a</li>
	 *         <li> 0 otherwise</li></ul>
	 */
	public function dependencyCompare(StructureElementInterface $a,
		StructureElementInterface $b)
	{
		if ($this->dependsOn($a, $b))
			return 1;
		return ($this->dependsOn($b, $a) ? -1 : 0);
	}

	public function __construct()
	{}
}
