<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Configuration;

use NoreSources\DateTimeZone;
use NoreSources\Container\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\Type\TypeConversion;

trait ConfiguratorTrait
{

	public function canGet($key)
	{
		return $this->canSet($key);
	}

	public function offsetExists($key)
	{
		return $this->canGet($key);
	}

	protected function normalizeValue($key, $value)
	{
		switch ($key)
		{
			case K::CONFIGURATION_KEY_CONSTRAINTS:
				return TypeConversion::toBoolean($value);
			case K::CONFIGURATION_SUBMIT_TIMEOUT:
				return TypeConversion::toInteger($value);
			case K::CONFIGURATION_TIMEZONE:
				{
					if ($value instanceof \DateTimeZone)
						return $value;
					return DateTimeZone::createFromDescription($value);
				}
		}
		return $value;
	}

	protected function setCachedValue($key, $value)
	{
		if (!isset($this->valueCache))
			$this->valueCache = [];
		$this->valueCache[$key] = $value;
		return $value;
	}

	protected function getCachedValue($key, $missing = NULL)
	{
		if (!(isset($this->valueCache) &&
			\array_key_exists($key, $this->valueCache)))
			return $missing;

		return $this->valueCache[$key];
	}

	protected function unsetCachedValue($key)
	{
		if (isset($this->valueCache))
			Container::removeKey($this->valueCache, $key);
	}

	/**
	 *
	 * @var array
	 */
	protected $valueCache;
}
