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

/**
 * Implementation of ColumnDescriptionInterface using an \ArrayObject of column properties
 */
class ArrayColumnDescription implements ColumnDescriptionInterface
{
	use ColumnDescriptionTrait;

	public function __construct($properties = array())
	{
		$this->initializeColumnProperties($properties);
		;
	}
}

