<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\Type\TypeDescription;

/**
 * Structure-related helper methods
 */
class Structure
{

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
}