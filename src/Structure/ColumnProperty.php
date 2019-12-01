<?php
namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\SQL\Constants as K;

interface ColumnPropertyMap
{

	/**
	 *
	 * @param string $key
	 */
	function hasColumnProperty($key);

	/**
	 *
	 * @param string $key
	 * @return boolean
	 */
	function getColumnProperty($key);

	/**
	 * Get all column properties
	 * #return array
	 */
	function getColumnProperties();

	/**
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	function setColumnProperty($key, $value);

	/**
	 * Remove a column property
	 *
	 * @param string $key
	 */
	function removeColumnProperty($key);
}

trait ColumnPropertyMapTrait
{

	public function initializeColumnProperties($properties = array())
	{
		$this->columnProperties = [
			K::COLUMN_PROPERTY_ACCEPT_NULL => true,
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
		return ns\Container::keyExists($this->columnProperties, $key);
	}

	public function getColumnProperty($key)
	{
		if (ns\Container::keyExists($this->columnProperties, $key))
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
			case K::COLUMN_PROPERTY_ACCEPT_NULL:
				$value = ($value ? true : false);
			break;
			case K::COLUMN_PROPERTY_DATA_SIZE:
			case K::COLUMN_PROPERTY_DATA_TYPE:
			case K::COLUMN_PROPERTY_FRACTION_DIGIT_COUNT:
				$value = ns\TypeConversion::toInteger($value);
			break;
			case K::COLUMN_PROPERTY_MEDIA_TYPE:
				if (!($value instanceof ns\MediaType))
					$value = new ns\MediaType($value);
			break;
			case K::COLUMN_PROPERTY_UNSERIALIZER:
				if (!($value instanceof DataUnserializer))
					throw new \InvalidArgumentException(
						'Invalid value type ' . ns\TypeDescription::getName($value) .
						' for property ' . $key);
			break;
		}

		$this->columnProperties[$key] = $value;
	}

	public function removeColumnProperty($key)
	{
		if (ns\Container::keyExists($this->columnProperties, $key))
			unset($this->columnProperties[$key]);
	}

	/**
	 *
	 * @var array
	 */
	private $columnProperties;
}

class ArrayColumnPropertyMap implements ColumnPropertyMap
{
	use ColumnPropertyMapTrait;

	public function __construct($properties = array())
	{
		$this->initializeColumnProperties($properties);
		;
	}
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
			case K::COLUMN_PROPERTY_DATA_SIZE:
			case K::COLUMN_PROPERTY_FRACTION_DIGIT_COUNT:
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
			K::COLUMN_PROPERTY_ACCEPT_NULL => true,
			K::COLUMN_PROPERTY_AUTO_INCREMENT => false,
			K::COLUMN_PROPERTY_FRACTION_DIGIT_COUNT => 0,
			K::COLUMN_PROPERTY_DATA_SIZE => 0,
			K::COLUMN_PROPERTY_DATA_TYPE => K::DATATYPE_STRING,
			K::COLUMN_PROPERTY_ENUMERATION => null,
			K::COLUMN_PROPERTY_DEFAULT_VALUE => null
		];
	}

	private static $defaultValues = null;
}