<?php
namespace NoreSources\SQL\DBMS;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;

/**
 * Reference implementation of TypeInterface::getTypeMaxLength()
 */
trait TypeMaxLengthTrait
{

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

		$max = 1;
		while ($maxValue > 10)
		{
			$maxValue /= 10;
			$max++;
		}

		return $max;
	}
}
