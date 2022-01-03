<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure\Traits;

use NoreSources\SQL\Structure\StructureElementInterface;

trait ForIdentifierTrait
{

	public function forStructure(StructureElementInterface $element)
	{
		return $this->identifier($element);
	}
}
