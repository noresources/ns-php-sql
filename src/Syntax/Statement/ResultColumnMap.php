<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\IndexedAssetMap;
use NoreSources\SQL\Structure\ArrayColumnDescription;

/**
 * Result columns of a SELECT statement or a Recordset
 */
class ResultColumnMap extends IndexedAssetMap

{

	public function __construct()
	{}

	/**
	 *
	 * @param integer $index
	 * @param array $data
	 *        	Column property
	 * @param string $as
	 *        	Optional alias
	 */
	public function setColumn($index, $data, $as = null)
	{
		$data = new ArrayColumnDescription(
			Container::createArray($data));

		if (\is_string($as) && \strlen($as))
			$data->setColumnProperty(K::COLUMN_NAME, $as);

		$this->offsetSet($index, $data);
	}
}

