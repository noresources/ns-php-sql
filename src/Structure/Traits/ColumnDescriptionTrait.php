<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
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
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataTypeProviderInterface;
use NoreSources\SQL\NameProviderInterface;
use NoreSources\SQL\Structure\ColumnPropertyHelper;

/**
 * Reference implementation of ColumnDescriptionInterface
 */
trait ColumnDescriptionTrait
{

	/**
	 *
	 * @return integer Column data type
	 */
	public function getDataType()
	{
		if ($this->has(K::COLUMN_DATA_TYPE))
			return $this->get(K::COLUMN_DATA_TYPE);
		return K::DATATYPE_UNDEFINED;
	}

	/**
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function has($key)
	{
		if (isset($this->columnProperties))
			return Container::keyExists($this->columnProperties, $key);
		return false;
	}

	/**
	 *
	 * @param string $key
	 * @return string|number|boolean|NULL
	 */
	public function get($key)
	{
		if (isset($this->columnProperties))
			if (Container::keyExists($this->columnProperties, $key))
				return $this->columnProperties[$key];

		return ColumnPropertyHelper::get($key);
	}

	public function getIterator()
	{
		return new \ArrayIterator(
			$this->columnProperties ? $this->columnProperties : []);
	}

	public function getArrayCopy()
	{
		return $this->columnProperties;
	}

	public function setColumnProperty($key, $value)
	{
		if (!isset($this->columnProperties))
			$this->columnProperties = [];
		$this->columnProperties[$key] = ColumnPropertyHelper::normalizeValue(
			$key, $value);
		;
	}

	/**
	 *
	 * @param array $data
	 */
	protected function initializeColumnProperties($data = array())
	{
		$this->columnProperties = [
			K::COLUMN_DATA_TYPE => K::DATATYPE_STRING
		];

		if (Container::isTraversable($data))
			foreach ($data as $key => $value)
				$this->setColumnProperty($key, $value);

		if ($data instanceof DataTypeProviderInterface)
			$this->setColumnProperty(K::COLUMN_DATA_TYPE,
				$data->getDataType());
		if ($data instanceof NameProviderInterface)
			$this->setColumnProperty(K::COLUMN_NAME, $data->getName());

		if (!$this->has(K::COLUMN_NAME) &&
			TypeDescription::hasStringRepresentation($data) &&
			($name = TypeConversion::toString($data)) && !empty($name))
			$this->setColumnProperty(K::COLUMN_NAME, $name);
	}

	/**
	 *
	 * @var array
	 */
	private $columnProperties;
}

