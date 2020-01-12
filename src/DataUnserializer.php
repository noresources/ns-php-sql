<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\SQL\Structure\ColumnPropertyMap;

interface DataUnserializer
{

	/**
	 * Unserialize data from DBMS record column
	 *
	 * @param ColumnPropertyMap $column
	 *        	Column properties
	 * @param mixed $data
	 *        	Data to unserialize
	 * @return mixed Unserialized data
	 */
	function unserializeColumnData(ColumnPropertyMap $column, $data);
}
