<?php
namespace NoreSources\SQL\DBMS;

/**
 * Provide an interface for string literal escaping and quoting.
 */
interface StringSerializerInterface
{

	/**
	 * Escape and quote a string literal to be used safely in a SQL statement.
	 *
	 * @param mixed $value
	 *        	Any stringifiable value
	 * @return strinS Representation of $value as a string suitable to be used in a
	 *         SQL statement
	 *         for a given DBMS.
	 */
	function quoteStringValue($value);
}
