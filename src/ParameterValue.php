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

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\Value;

/**
 * Value of statement parameter.
 *
 * This class is intented to be used in a array given to Connection::executeStatement() method
 */
class ParameterValue
{

	/**
	 * Parameter value
	 *
	 * @var mixed
	 */
	public $value;

	/**
	 * A combination of \NoreSources\
	 *
	 * @var integer
	 */
	public $type;

	/**
	 *
	 * @param mixed $value
	 *        	Parameter value
	 * @param integer $type
	 *        	Parameter value type. If set to DATATYPE_UNDEFINED, the type will be determined automatically
	 */
	public function __construct($value, $type = K::DATATYPE_UNDEFINED)
	{
		if ($type == K::DATATYPE_UNDEFINED)
			$type = Value::dataTypeFromValue($value);
	}
}