<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

/**
 * Provide an iterface for binary value escaping and quoting.
 */
interface BinaryDataSerializerInterface
{

	/**
	 * Escape and quote a value to be used in a SQL statement.
	 *
	 * @param mixed $value
	 *        	Any value
	 * @return string Binary string representation of $value as a string suitable to be used in a
	 *         SQL statement
	 *         for a given DBMS.
	 */
	function quoteBinaryData($value);
}
