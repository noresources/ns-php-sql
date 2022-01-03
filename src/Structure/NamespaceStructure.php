<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\Structure\Traits\StructureElementContainerTrait;
use NoreSources\SQL\Structure\Traits\StructureElementTrait;

/**
 * Table set structure definition
 */
class NamespaceStructure implements StructureElementContainerInterface,
	StructureElementInterface
{
	use StructureElementTrait;
	use StructureElementContainerTrait;

	public function __construct($name,
		StructureElementContainerInterface $parent = null)
	{
		$this->initializeStructureElement($name, $parent);
		$this->initializeStructureElementContainer();
	}

	public function __clone()
	{
		$this->cloneStructureElement();
		$this->cloneStructureElementContainer();
	}
}
