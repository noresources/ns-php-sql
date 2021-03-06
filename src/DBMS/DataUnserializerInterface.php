<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\SQL\Structure\ColumnDescriptionInterface;

interface DataUnserializerInterface
{

	/**
	 * Unserialize data from DBMS record column
	 *
	 * @param ColumnDescriptionInterface $columnDescription
	 *        	Column properties
	 * @param mixed $data
	 *        	Data to unserialize
	 * @return mixed Unserialized data
	 */
	function unserializeColumnData($columnDescription, $data);
}

