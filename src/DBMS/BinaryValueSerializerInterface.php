<?php
namespace NoreSources\SQL\DBMS;

/**
 * Provide an iterface for binary value escaping and quoting.
 */
interface BinaryValueSerializerInterface
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
	function quoteBinaryValue($value);
}
