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

use NoreSources\SQL\Constants as K;

/**
 * Table column properties
 */
class ColumnStructure extends StructureElement implements ColumnPropertyMap
{

	const DATATYPE = K::COLUMN_PROPERTY_DATA_TYPE;

	const AUTO_INCREMENT = K::COLUMN_PROPERTY_AUTO_INCREMENT;

	const ACCEPT_NULL = K::COLUMN_PROPERTY_ACCEPT_NULL;

	const DATA_SIZE = K::COLUMN_PROPERTY_DATA_SIZE;

	const FRACTION_DIGIT_COUNT = K::COLUMN_PROPERTY_FRACTION_DIGIT_COUNT;

	const ENUMERATION = K::COLUMN_PROPERTY_ENUMERATION;

	const DEFAULT_VALUE = K::COLUMN_PROPERTY_DEFAULT_VALUE;

	use ColumnPropertyMapTrait;

	public function __construct(/*TableStructure */$a_tableStructure, $name)
	{
		parent::__construct($name, $a_tableStructure);
		$this->initializeColumnProperties();
	}

	/**
	 * Clone default value if any.
	 */
	public function __clone()
	{
		parent::__clone();
		if ($this->hasColumnProperty(self::DEFAULT_VALUE))
		{
			$this->setColumnProperty(self::DEFAULT_VALUE,
				clone $this->getColumnProperty(self::DEFAULT_VALUE));
		}
	}
}