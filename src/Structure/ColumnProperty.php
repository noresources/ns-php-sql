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
		if (!ColumnPropertyDefault::isValid($key))
			throw new \DomainException('Invalid column property key ' . $key);

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

class ColumnPropertyDefault
{

	public static function isValid($key)
	{
		if (self::$defaultValues == null)
			self::initialize();

		return \array_key_exists($key, self::$defaultValues);
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
			K::COLUMN_PROPERTY_FRACTION_DIGIT_COUNT => [
				'set' => true,
				'value' => 0
			],
			K::COLUMN_PROPERTY_DATA_SIZE => [
				'set' => false,
				'value' => 0
			],
			K::COLUMN_PROPERTY_DATA_TYPE => K::DATATYPE_STRING,
			K::COLUMN_PROPERTY_ENUMERATION => [
				'set' => false,
				'value' => null
			],
			K::COLUMN_PROPERTY_DEFAULT_VALUE => [
				'set' => false,
				'value' => null
			]
		];
	}

	private static $defaultValues = null;
}