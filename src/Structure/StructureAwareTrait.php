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

use NoreSources\TypeDescription;

/**
 * Implements StructureAwareInterface
 */
trait StructureAwareTrait
{

	public function getStructure()
	{
		return $this->structureReference;
	}

	protected function setStructure($structure)
	{
		if ($structure instanceof StructureElement)
			$this->structureReference = $structure;
		elseif (is_file($structure))
			$this->structureReference = StructureSerializerFactory::structureFromFile($filename);
		else
			throw new \InvalidArgumentException(
				TypeDescription::getName($structure) .
				' is not a valid argument. Instance of StructureElement or filename expected');
	}

	private $structureReference;
}

