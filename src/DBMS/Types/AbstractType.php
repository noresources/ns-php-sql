<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Types;

use NoreSources\Container\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\TypeInterface;

abstract class AbstractType implements TypeInterface
{

	public function getDataType()
	{
		if ($this->has(K::TYPE_DATA_TYPE))
			return $this->get(K::TYPE_DATA_TYPE);
		return K::DATATYPE_UNDEFINED;
	}

	public function getTypeFlags()
	{
		if ($this->has(K::TYPE_FLAGS))
			return $this->get(K::TYPE_FLAGS);

		$dataType = Container::keyValue($this, K::TYPE_DATA_TYPE,
			K::DATATYPE_UNDEFINED);

		return 0;
	}

	public function acceptDefaultValue($withDataType = 0)
	{
		$acceptedDataType = 0;
		if ($this->has(K::TYPE_DEFAULT_DATA_TYPE))
			$acceptedDataType = $this->get(K::TYPE_DEFAULT_DATA_TYPE);
		elseif ($this->has(K::TYPE_DATA_TYPE))
			$acceptedDataType = K::DATATYPE_NULL |
				$this->get(K::TYPE_DATA_TYPE);

		if (($acceptedDataType & $withDataType) != 0)
			return true;

		return ($withDataType & K::DATATYPE_NULL) == K::DATATYPE_NULL;
	}

	public function getTypeMaxLength()
	{
		if ($this->has(K::TYPE_MAX_LENGTH))
			return $this->get(K::TYPE_MAX_LENGTH);

		if ($this->has(K::TYPE_DATA_TYPE))
		{
			$dataType = $this->get(K::TYPE_DATA_TYPE);
			if ($dataType & K::DATATYPE_NUMBER)
				return $this->getTypeMaxPrecision();

			if ($dataType == K::DATATYPE_STRING ||
				$dataType == K::DATATYPE_BINARY)
			{
				if ($this->has(K::TYPE_SIZE))
					return \intval($this->get(K::TYPE_SIZE) / 8);
			}
		}

		return INF;
	}

	/**
	 * Mumeric type integer max precision based on type size
	 *
	 * @return integer
	 */
	private function getTypeMaxPrecision()
	{
		$size = Container::keyValue($this, K::TYPE_SIZE, INF);
		if ($size == INF || $size == 0)
			return INF;
		$maxValue = \pow(2, $size) - 1;
		$m = \strval($maxValue);
		if (($p = \strpos($maxValue, 'E+')) != false)
			return \intval(\substr($maxValue, $p + 2)) +
				\strpos($maxValue, '.');
		return \strlen($maxValue);
	}
}
