<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\Structure\Inspector\StructureInspector;
use NoreSources\Type\TypeDescription;

/**
 * Structure-related helper methods
 */
class Structure
{

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
	 * @param StructureElementInterface $structure
	 * @param string $newName
	 * @throws \RuntimeException
	 * @return StructureElementInterface
	 */
	public static function duplicate(
		StructureElementInterface $structure, $newName)
	{
		if (!\method_exists($structure, 'setName'))
			throw new \RuntimeException(
				'Unable to change name of ' .
				TypeDescription::getName($structure));
		$duplicated = clone $structure;
		$duplicated->setName($newName);
		if ($structure->getParentElement())
			$structure->getParentElement()->appendElement($duplicated);
		return $duplicated;
	}

	/**
	 * Indicates if the given structure lement may contains persistent data
	 *
	 * @param StructureElementInterface|string $elementOrElementClassname
	 * @return boolean
	 * @deprecated Use StructureInspector
	 */
	public static function hasData($elementOrElementClassname)
	{
		return StructureInspector::getInstance()->hasData(
			$elementOrElementClassname);
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
	public static function isTableComponent(
		StructureElementInterface $element)
	{
		return ($element instanceof ColumnStructure) ||
			($element instanceof TableConstraintInterface);
	}

	/**
	 *
	 * @param StructureElementInterface $a
	 * @param StructureElementInterface $b
	 * @return boolean
	 * @deprecated Use StructureInspector
	 */
	public static function conflictsWith(StructureElementInterface $a,
		StructureElementInterface $b)
	{
		return StructureInspector::getInstance()->conflictsWith($a, $b);
	}

	/**
	 * Get the root StructureElementContainerInterface of the givven element
	 *
	 * @param StructureElementInterface $e
	 * @return StructureElementInterface
	 * @deprecated Use StructureInspector
	 */
	public static function getRootElement(StructureElementInterface $e)
	{
		return StructureInspector::getInstance()->getRootElement($e);
	}

	/**
	 *
	 * @param StructureElementInterface $element
	 * @return array<StructureElementInterface> Siblings of $element
	 *
	 * @deprecated Use StructureInspector
	 */
	public static function getSiblingElements(
		StructureElementInterface $element)
	{
		return StructureInspector::getSiblingElements($element);
	}

	/**
	 *
	 * @param StructureElementInterface $p
	 * @param StructureElementInterface $c
	 * @return boolean TRUE if $p is an ancestor of $c
	 *
	 * @deprecated Use StructureInspector
	 */
	public static function isAncestorOf(StructureElementInterface $p,
		StructureElementInterface $c)
	{
		return StructureInspector::getInstance()->isAncestorOf($p, $c);
	}

	/**
	 * Get the list of ancestor of a given element
	 *
	 * @param StructureElementInterface $e
	 * @return StructureElementInterface[] Array of ancestor from the most distant to the closest
	 *
	 * @deprecated Use StructureInspector
	 */
	public static function ancestorTree(StructureElementInterface $e)
	{
		return StructureInspector::getInstance()->getAncestorTree($e);
	}

	/**
	 * Get the closest command ancestor of two elements
	 *
	 * @param StructureElementInterface $a
	 * @param StructureElementInterface $b
	 * @return StructureElementInterface|NULL
	 *
	 * @deprecated Use StructureInspector
	 */
	public static function commonAncestor(StructureElementInterface $a,
		StructureElementInterface $b)
	{
		return StructureInspector::getInstance()->getCommonAncestor($a,
			$b);
	}

	/**
	 *
	 * @param StructureElementInterface $a
	 * @param StructureElementInterface $b
	 * @return boolean TRUE if $a depends on $b
	 *
	 * @deprecated Use StructureInspector
	 */
	public static function dependsOn(StructureElementInterface $a,
		StructureElementInterface $b)
	{
		return StructureInspector::getInstance()->dependsOn($a, $b);
	}

	/**
	 * Sort eleent by their mutual dependency
	 *
	 * @param StructureElementInterface $a
	 * @param StructureElementInterface $b
	 * @return number <ul><li>> 1 if $a depends on $b</li>
	 *         <li> &lt; 1 if $b depends on $a</li>
	 *         <li> 0 otherwise</li></ul>
	 * @deprecated Use StructureInspector
	 */
	public static function dependencyCompare(
		StructureElementInterface $a, StructureElementInterface $b)
	{
		return StructureInspector::getInstance()->dependencyCompare($a,
			$b);
	}
}