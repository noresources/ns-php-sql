<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Traits;

use NoreSources\Container;
use NoreSources\TypeConversion;
use NoreSources\TypeDescription;
use NoreSources\MediaType\MediaType;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\DataUnserializerInterface;
use NoreSources\SQL\Structure\ColumnPropertyNotFoundException;
use NoreSources\SQL\Structure\StructureException;

/**
 * Reference implementation of ColumnDescriptionInterface
 */
trait ColumnDescriptionTrait
{

	public function initializeColumnProperties($properties = array())
	{
		$this->columnProperties = [
			K::COLUMN_DATA_TYPE => K::DATATYPE_STRING
		];

		if (Container::isTraversable($properties))
		{
			foreach ($properties as $key => $value)
			{
				$this->setColumnProperty($key, $value);
			}
		}
	}

	public function getDataType()
	{
		if ($this->has(K::COLUMN_DATA_TYPE))
			return $this->get(K::COLUMN_DATA_TYPE);
		return K::DATATYPE_UNDEFINED;
	}

	public function has($key)
	{
		return Container::keyExists($this->columnProperties, $key);
	}

	public function get($key)
	{
		if (Container::keyExists($this->columnProperties, $key))
			return $this->columnProperties[$key];
		return ColumnPropertyDefault::get($key);
	}

	public function getIterator()
	{
		return new \ArrayIterator($this->columnProperties);
	}

	public function setColumnProperty($key, $value)
	{
		if (!ColumnPropertyDefault::isValidKey($key))
			throw new StructureException(
				'Invalid column property key ' . $key);

		switch ($key)
		{
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

		$this->columnProperties[$key] = $value;
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
			K::COLUMN_FLAGS => 0,
			K::COLUMN_FRACTION_SCALE => 0,
			K::COLUMN_LENGTH => 0,
			K::COLUMN_DATA_TYPE => K::DATATYPE_STRING | K::DATATYPE_NULL,
			K::COLUMN_ENUMERATION => null,
			K::COLUMN_DEFAULT_VALUE => null,
			K::COLUMN_MEDIA_TYPE => null
		];
	}

	private static $defaultValues = null;
}