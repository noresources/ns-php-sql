<?php
/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

// Aliases
use NoreSources\SQL\Structure\StructureElement;
use NoreSources\SQL\Structure\StructureSerializerFactory;
use NoreSources as ns;

trait ConnectionStructureTrait
{

	public function getStructure()
	{
		return $this->connectionStructure;
	}

	protected function setStructure($structure)
	{
		if ($structure instanceof StructureElement)
			$this->connectionStructure = $structure;
		elseif (is_file($structure))
			$this->connectionStructure = StructureSerializerFactory::structureFromFile($filename);
		else
			throw new \InvalidArgumentException(
				ns\TypeDescription::getName($structure) .
				' is not a valid argument. Instance of StructureElement or filename expected');
	}

	private $connectionStructure;
}

