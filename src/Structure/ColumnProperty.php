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
			K::COLUMN_PROPERTY_ACCEPT_NULL => [
				'set' => true,
				'value' => true
			],
			K::COLUMN_PROPERTY_AUTO_INCREMENT => [
				'set' => true,
				'value' => false
			],
			K::COLUMN_PROPERTY_FRACTION_DIGIT_COUNT => [
				'set' => true,
				'value' => 0
			],
			K::COLUMN_PROPERTY_DATA_SIZE => [
				'set' => false,
				'value' => 0
			],
			K::COLUMN_PROPERTY_DATA_TYPE => [
				'set' => true,
				'value' => K::DATATYPE_STRING
			],
			K::COLUMN_PROPERTY_ENUMERATION => [
				'set' => false,
				'value' => null
			],
			K::COLUMN_PROPERTY_DEFAULT_VALUE => [
				'set' => false,
				'value' => null
			]
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
		if (ns\Container::keyExists($this->columnProperties, $key))
			return $this->columnProperties[$key]['set'];
		else
			throw new \DomainException('Invalid column property key ' . $key);
	}

	public function getColumnProperty($key)
	{
		if ($this->hasColumnProperty($key))
			return $this->columnProperties[$key]['value'];
	}

	public function getColumnProperties()
	{
		return \array_filter($this->columnProperties, function ($v) {
			return $v['set'];
		});
	}

	public function setColumnProperty($key, $value)
	{
		if (ns\Container::keyExists($this->columnProperties, $key))
		{
			$this->columnProperties[$key]['set'] = true;
			$this->columnProperties[$key]['value'] = $value;
		}
		else
			throw new \DomainException('Invalid column property key ' . $key);
	}

	public function removeColumnProperty($key)
	{
		if (ns\Container::keyExists($this->columnProperties, $key))
		{
			$this->columnProperties[$key]['set'] = false;
			$this->columnProperties[$key]['value'] = null;
		}
		else
			throw new \DomainException('Invalid column property key ' . $key);
	}

	/**
	 *
	 * @var array
	 */
	private $columnProperties;
}