<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement;

use NoreSources\TypeConversion;
use NoreSources\TypeDescription;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataTypeProviderInterface;
use NoreSources\SQL\Structure\ColumnDescriptionInterface;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\Traits\ColumnDescriptionTrait;

/**
 * Record column description
 */
class ResultColumn implements ColumnDescriptionInterface
{

	use ColumnDescriptionTrait;

	/**
	 *
	 * @var string
	 */
	public $name;

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

		if ($data instanceof ColumnDescriptionInterface)
		{
			$this->initializeColumnProperties($data);
		}
		elseif ($data instanceof DataTypeProviderInterface)
		{
			$this->initializeColumnProperties(
				[
					K::COLUMN_DATA_TYPE => $data->getDataType()
				]);
		}
		else
			$this->initializeColumnProperties();
	}
}
