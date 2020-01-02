<?php
namespace NoreSources\SQL;

use NoreSources as ns;
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

