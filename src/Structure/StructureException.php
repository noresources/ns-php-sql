<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\Structure\Traits\StructureProviderTrait;

class StructureException extends \Exception implements
	StructureProviderInterface
{

	use StructureProviderTrait;

	public function __construct($message,
		StructureElementInterface $element = null)
	{
		parent::__construct($message);
		if ($element instanceof StructureElementInterface)
			$this->setStructure($element);
	}
}

