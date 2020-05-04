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
 * Top-level structure container definition
 */
class DatasourceStructure implements StructureElementInterface, StructureElementContainerInterface
{

	use StructureElementContainerTrait;
	use StructureElementTrait;

	/**
	 *
	 * @param string $name
	 *        	Datasource class name
	 */
	public function __construct($name = 'datasource')
	{
		$name = (\is_string($name) && \strlen($name)) ? $name : TypeDescription::getLocalName(
			static::class);
		$this->initializeStructureElementContainer();
	}
}

