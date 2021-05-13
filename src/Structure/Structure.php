<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

/**
 * Structure-related helper methods
 */
class Structure
{

	/**
	 * Get the root StructureElementContainerInterface of the givven element
	 *
	 * @param StructureElementInterface $e
	 * @return StructureElementInterface
	 */
	public static function getRootElement(StructureElementInterface $e)
	{
		$parent = $e;
		while ($parent->getParentElement())
			$parent = $parent->getParentElement();

		return $parent;
	}

	/**
	 *
	 * @param StructureElementInterface $e
	 * @return \NoreSources\SQL\Structure\Identifier. Canonical identifier or empty identifier if
	 *         one of the element hierarchy does not have a name
	 */
	public static function makeIdentifier(StructureElementInterface $e)
	{
		$parent = $e;
		$names = [];
		while ($parent && !($parent instanceof DatasourceStructure))
		{
			$name = $parent->getName();
			if (empty($name))
				return Identifier::make(null);
			\array_unshift($names, $name);

			$parent = $parent->getParentElement();
		}

		return Identifier::make($names);
	}

	/**
	 * Get the element canonical key.
	 *
	 * The returned Identifier is guaranted to be unique and represent the complete hierarchy.
	 * It SHOULD NOT be used for persistent operations.
	 *
	 * @param StructureElementInterface $e
	 * @return \NoreSources\SQL\Structure\Identifier. Element canonical key composed
	 *         by hierarchy element names or key
	 *
	 */
	public static function makeCanonicalKey(
		StructureElementInterface $e)
	{
		$parent = $e;
		$keys = [];
		while ($parent && !($parent instanceof DatasourceStructure))
		{
			\array_unshift($keys, $parent->getElementKey());
			$parent = $parent->getParentElement();
		}

		return Identifier::make($keys);
	}

	/**
	 *
	 * @param StructureElementInterface $p
	 * @param StructureElementInterface $c
	 * @return boolean TRUE if $p is an ancestor of $c
	 */
	public static function isAncestorOf(StructureElementInterface $p,
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
	public static function ancestorTree(StructureElementInterface $e)
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
	public static function commonAncestor(StructureElementInterface $a,
		StructureElementInterface $b)
	{
		$aa = self::ancestorTree($a);
		$ab = self::ancestorTree($b);
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
	 *
	 * @param StructureElementInterface $a
	 * @param StructureElementInterface $b
	 * @return boolean TRUE if $a depends on $b
	 */
	public static function dependsOn(StructureElementInterface $a,
		StructureElementInterface $b)
	{
		if (self::isAncestorOf($b, $a))
			return true;

		$resolver = new StructureResolver();

		if ($a instanceof ColumnStructure &&
			(($t = $a->getParentElement()) instanceof TableStructure))
		{
			$resolver->setPivot($a);
			$fks = $t->getChildElements(
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
					if ($fc == $b || self::dependsOn($fc, $b))
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
				if ($fkt == $b || self::dependsOn($fkt, $b))
					return true;
			}
		}
		elseif ($a instanceof ForeignKeyTableConstraint &&
			$a->getParentElement())
		{
			$resolver->setPivot($a->getParentElement());
			$fkt = $resolver->findTable($a->getForeignTable());
			if ($fkt == $b || self::dependsOn($fkt, $b))
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
	public static function dependencyCompare(
		StructureElementInterface $a, StructureElementInterface $b)
	{
		if (self::dependsOn($a, $b))
			return 1;
		return (self::dependsOn($b, $a) ? -1 : 0);
	}
}