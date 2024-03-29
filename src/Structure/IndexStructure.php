<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\Structure\Traits\IndexDescriptionTrait;
use NoreSources\SQL\Structure\Traits\StructureElementTrait;

/**
 * Standalone table index.
 */
class IndexStructure implements StructureElementInterface,
	IndexDescriptionInterface
{
	use StructureElementTrait;
	use IndexDescriptionTrait;

	public function __construct($name, $parent = null)
	{
		$this->initializeStructureElement($name, $parent);
	}

	public function __clone()
	{
		$this->cloneStructureElement();
		if (isset($this->whereConstraints))
			$this->whereConstraints = clone $this->whereConstraints;
	}
}
