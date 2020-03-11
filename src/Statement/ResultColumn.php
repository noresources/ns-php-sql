<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

// Namespace
namespace NoreSources\SQL\Statement;

// Aliases
use NoreSources\TypeConversion;
use NoreSources\TypeDescription;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\ColumnPropertyMap;
use NoreSources\SQL\Structure\ColumnPropertyMapTrait;
use NoreSources\SQL\Structure\ColumnStructure;

/**
 * Record column description
 */
class ResultColumn implements ColumnPropertyMap
{

	use ColumnPropertyMapTrait;

	/**
	 *
	 * @var string
	 */
	public $name;

	/**
	 *
	 * @property-read integer $dataType
	 * @param string $member
	 * @throws \InvalidArgumentException
	 * @return boolean|number|NULL|string|string
	 */
	public function __get($member)
	{
		if ($member == 'dataType')
		{
			if ($this->hasColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE))
				return $this->getColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE);
			return K::DATATYPE_UNDEFINED;
		}

		throw new \InvalidArgumentException(
			$member . ' is not a member of ' . TypeDescription::getName($this));
	}

	/**
	 *
	 * @property-write integer $dataType
	 * @param string $member
	 * @param mixed $value
	 * @throws \InvalidArgumentException
	 */
	public function __set($member, $value)
	{
		if ($member == 'dataType')
		{
			$this->setColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE, $value);
			return;
		}

		throw new \InvalidArgumentException(
			$member . ' is not a member of ' . TypeDescription::getName($this));
	}

	/**
	 *
	 * @param integer|ColumnStructure $data
	 */
	public function __construct($data)
	{
		if ($data instanceof ColumnStructure)
			$this->name = $data->getName();
		elseif (TypeDescription::hasStringRepresentation($data))
			$this->name = TypeConversion::toString($data);

		if ($data instanceof ColumnStructure)
		{
			$this->initializeColumnProperties($data->getColumnProperties());
		}
		else
			$this->initializeColumnProperties();
	}
}
