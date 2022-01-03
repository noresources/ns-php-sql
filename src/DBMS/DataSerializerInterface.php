<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\SQL\Structure\ColumnDescriptionInterface;

/**
 * Provide an API for classes that can transform PHP value and objects to
 * something understandable by the target DBMS
 */
interface DataSerializerInterface
{

	/**
	 * Serialize a value to be stored in a DBMS storage system
	 *
	 * @param ColumnDescriptionInterface $description
	 *        	Column description
	 * @param mixed $data
	 *        	Data to serialize
	 */
	function serializeColumnData($description, $data);

	/**
	 * Serialize a data of a given type
	 *
	 * @param mixed $data
	 *        	Data to serialize/quote
	 * @param integer $dataType
	 *        	Data type
	 *
	 *  @note This method is a less accurate version of serializeColumnData() thant only take
	 *        	care of the value type. It should be used internaly by serializeColumnData() implementations for the most basic cases.
	 */
	function serializeData($data, $dataType);
}
