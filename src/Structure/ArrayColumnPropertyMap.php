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

use NoreSources\SQL\Constants as K;

class ArrayColumnPropertyMap implements ColumnPropertyMap
{
	use ColumnPropertyMapTrait;

	public function __construct($properties = array())
	{
		$this->initializeColumnProperties($properties);
		;
	}
}

