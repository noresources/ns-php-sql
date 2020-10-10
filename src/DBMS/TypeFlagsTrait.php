<?php
namespace NoreSources\SQL\DBMS;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;

trait TypeFlagsTrait
{

	public function getTypeFlags()
	{
		if ($this->has(K::TYPE_FLAGS))
			return $this->get(K::TYPE_FLAGS);

		$dataType = Container::keyValue($this, K::TYPE_DATA_TYPE,
			K::DATATYPE_UNDEFINED);

		if ($dataType & K::DATATYPE_NUMBER)
			return K::TYPE_FLAGS_NUMBER_DEFAULT;
		return K::TYPE_FLAGS_DEFAULT;
	}
}
