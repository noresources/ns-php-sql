<?php
namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\SQL\Constants as K;

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
