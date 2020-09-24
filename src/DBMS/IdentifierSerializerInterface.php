<?php
namespace NoreSources\SQL\DBMS;

/**
 * Provide an iterface for DBMS identifier escaping and quoting.
 */
interface IdentifierSerializerInterface
{

	/**
	 * Escape and quote a structure element name to be used in a SQL statement.
	 *
	 * @return string
	 */
	function quoteIdentifier($identifier);
}
