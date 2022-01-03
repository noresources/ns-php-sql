<?php
/**
 * Copyright © 2012 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Traits;

use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureSerializerFactory;
use NoreSources\Type\TypeDescription;

/**
 * Implements StructureProviderInterface
 */
trait StructureProviderTrait
{

	public function getStructure()
	{
		return $this->structureReference;
	}

	protected function setStructure($structure)
	{
		if ($structure instanceof StructureElementInterface)
			$this->structureReference = $structure;
		elseif (is_file($structure))
		{
			$serializer = StructureSerializerFactory::getInstance();
			$this->structureReference = $serializer->structureFromFile(
				$filename);
		}
		else
			throw new \InvalidArgumentException(
				TypeDescription::getName($structure) .
				' is not a valid argument. Instance of StructureElement or filename expected');
	}

	private $structureReference;
}

