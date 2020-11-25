<?php

/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\TypeConversion;
use NoreSources\TypeDescription;
use NoreSources\MediaType\MediaType;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\DataUnserializerInterface;

class ColumnPropertyHelper
{

	public static function normalizeValue($key, $value)
	{
		switch ($key)
		{
			case K::COLUMN_NAME:
				$value = TypeConversion::toString($value);
			break;
			case K::COLUMN_LENGTH:
			case K::COLUMN_DATA_TYPE:
			case K::COLUMN_FRACTION_SCALE:
				$value = TypeConversion::toInteger($value);
			break;
			case K::COLUMN_MEDIA_TYPE:
				if (!($value instanceof MediaType))
					$value = MediaType::fromString($value);
			break;
			case K::COLUMN_UNSERIALIZER:
				if (!($value instanceof DataUnserializerInterface))
					throw new \InvalidArgumentException(
						'Invalid value type ' .
						TypeDescription::getName($value) .
						' for property ' . $key);
			break;
		}

		return $value;
	}

	/**
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return boolean
	 */
	public static function isValidValue($key, $value)
	{
		if (self::$defaultValues == null)
			self::initialize();

		switch ($key)
		{
			case K::COLUMN_NAME:
				return (TypeDescription::hasStringRepresentation($value) &&
					!empty($value));
			case K::COLUMN_UNSERIALIZER:
				return ($value instanceof DataUnserializerInterface);
			case K::COLUMN_DATA_TYPE:
			case K::COLUMN_LENGTH:
			case K::COLUMN_FRACTION_SCALE:
				return is_int($value);
		}

		return true;
	}

	public static function get($key)
	{
		if (self::$defaultValues == null)
			self::initialize();

		if (\array_key_exists($key, self::$defaultValues))
			return self::$defaultValues[$key];

		throw new ColumnPropertyNotFoundException($key);
	}

	private static function initialize()
	{
		self::$defaultValues = [
			K::COLUMN_FLAGS => 0
		];
	}

	private static $defaultValues = null;
}