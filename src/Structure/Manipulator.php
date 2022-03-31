<?php

/**
 * Copyright © 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SingletonTrait;
use NoreSources\Container\Container;
use NoreSources\SQL\Structure\Inspector\StructureInspector;
use NoreSources\Type\TypeDescription;

class Manipulator
{
	use SingletonTrait;

	/**
	 *
	 * @param StructureElementInterface $structure
	 * @param string $newName
	 * @throws \RuntimeException
	 * @return StructureElementInterface
	 */
	public function duplicate(StructureElementInterface $structure,
		$newName)
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
	 * Remove element, childrens and dependencies
	 *
	 * @param StructureElementInterface $element
	 */
	public function drop(StructureElementInterface $element)
	{
		$inspector = StructureInspector::getInstance();

		$references = $inspector->getReverseReferenceMap($element);
		$deps = $inspector->getReferences($element);
		var_dump(
			Container::map($references,
				function ($n, $a) {
					return $n . ' -> ' .
					Container::implodeValues($a, PHP_EOL,
						function ($e) {
							return TypeDescription::getLocalName($e);
						});
				}));

		if ($element->getParentElement())
		{
			$element->getParentElement()->offsetUnset(
				$element->getElementKey());
		}
	}
}
