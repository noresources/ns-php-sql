<?php
/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\SQL\Structure\ColumnPropertyMap;

interface DataSerializer
{

	/**
	 * Serialize a value to be stored in a DBMS storage system
	 *
	 * @param ColumnPropertyMap $column
	 * @param unknown $data
	 */
	function serializeColumnData(ColumnPropertyMap $column, $data);
}
