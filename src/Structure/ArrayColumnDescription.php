<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\Traits\ColumnDescriptionTrait;

/**
 * Implementation of ColumnDescriptionInterface using an \ArrayObject of column properties
 */
class ArrayColumnDescription implements ColumnDescriptionInterface
{
	use ColumnDescriptionTrait;

	public function getName()
	{
		return $this->get(K::COLUMN_NAME);
	}

	public function __construct($properties = array())
	{
		$this->initializeColumnProperties($properties);
		;
	}
}

