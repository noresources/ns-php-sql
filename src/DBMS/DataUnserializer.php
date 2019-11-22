<?php
namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\SQL\Constants as K;

interface DataUnserializer
{

	/**
	 * Unserialize data from DBMS record column
	 *
	 * @param ResultColumn $column
	 *        	Column
	 * @param mixed $data
	 *        	Data to unserialize
	 * @return mixed Unserialized data
	 */
	function unserializeData(ResultColumn $column, $data);
}
