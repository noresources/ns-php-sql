<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\Structure\Traits\StructureElementTrait;

/**
 * View structure definition
 */
class ViewStructure implements StructureElementInterface
{

	use StructureElementTrait;

	/**
	 *
	 * @param string $name
	 * @param NamespaceStructure $parent
	 *        	Parent element
	 */
	public function __construct($name,
		StructureElementContainerInterface $parent = null)
	{
		$this->initializeStructureElement($name, $parent);
	}

	public function __clone()
	{
		$this->cloneStructureElement();
	}
}
