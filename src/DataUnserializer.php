<?php
namespace NoreSources\SQL;

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

