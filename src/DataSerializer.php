<?php
namespace NoreSources\SQL;

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
