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

use NoreSources\Container;
use NoreSources\TypeConversion;
use NoreSources\TypeDescription;
use NoreSources\MediaType\MediaType;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataUnserializer;

trait ColumnPropertyMapTrait
{

	public function initializeColumnProperties($properties = array())
	{
		$this->columnProperties = [
			K::COLUMN_PROPERTY_FLAGS => K::COLUMN_FLAGS_DEFAULT,
			K::COLUMN_PROPERTY_AUTO_INCREMENT => false,
			K::COLUMN_PROPERTY_DATA_TYPE => K::DATATYPE_STRING
		];

		if ($properties)
		{
			foreach ($properties as $key => $value)
			{
				$this->setColumnProperty($key, $value);
			}
		}
	}

	public function hasColumnProperty($key)
	{
		return Container::keyExists($this->columnProperties, $key);
	}

	public function getColumnProperty($key)
	{
		if (Container::keyExists($this->columnProperties, $key))
			return $this->columnProperties[$key];
		return ColumnPropertyDefault::get($key);
	}

	public function getColumnProperties()
	{
		return $this->columnProperties;
	}

	public function setColumnProperty($key, $value)
	{
		if (!ColumnPropertyDefault::isValidKey($key))
			throw new \DomainException('Invalid column property key ' . $key);

		switch ($key)
		{
			case K::COLUMN_PROPERTY_LENGTH:
			case K::COLUMN_PROPERTY_DATA_TYPE:
			case K::COLUMN_PROPERTY_FRACTION_SCALE:
				$value = TypeConversion::toInteger($value);
			break;
			case K::COLUMN_PROPERTY_MEDIA_TYPE:
				if (!($value instanceof MediaType))
					$value = new MediaType($value);
			break;
			case K::COLUMN_PROPERTY_UNSERIALIZER:
				if (!($value instanceof DataUnserializer))
					throw new \InvalidArgumentException(
						'Invalid value type ' . TypeDescription::getName($value) . ' for property ' .
						$key);
			break;
		}

		$this->columnProperties[$key] = $value;
	}

	public function removeColumnProperty($key)
	{
		if (Container::keyExists($this->columnProperties, $key))
			unset($this->columnProperties[$key]);
	}

	/**
	 *
	 * @var array
	 */
	private $columnProperties;
}

class ColumnPropertyDefault
{

	public static function isValidKey($key)
	{
		if (self::$defaultValues == null)
			self::initialize();

		return \array_key_exists($key, self::$defaultValues);
	}

	public static function isValidValue($key, $value)
	{
		if (self::$defaultValues == null)
			self::initialize();

		switch ($key)
		{
			case K::COLUMN_PROPERTY_UNSERIALIZER:
				return ($value instanceof DataUnserializer);
			case K::COLUMN_PROPERTY_DATA_TYPE:
			case K::COLUMN_PROPERTY_LENGTH:
			case K::COLUMN_PROPERTY_FRACTION_SCALE:
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

		throw new \DomainException('Invalid column property key ' . $key);
	}

	private static function initialize()
	{
		self::$defaultValues = [
			K::COLUMN_PROPERTY_FLAGS => K::COLUMN_FLAGS_DEFAULT,
			K::COLUMN_PROPERTY_AUTO_INCREMENT => false,
			K::COLUMN_PROPERTY_FRACTION_SCALE => 0,
			K::COLUMN_PROPERTY_LENGTH => 0,
			K::COLUMN_PROPERTY_DATA_TYPE => K::DATATYPE_STRING,
			K::COLUMN_PROPERTY_ENUMERATION => null,
			K::COLUMN_PROPERTY_DEFAULT_VALUE => null
		];
	}

	private static $defaultValues = null;
}