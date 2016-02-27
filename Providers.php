<?php

/**
 * Copyright © 2012-2015 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources as ns;

/**
 * Provide an access to Database object which
 * represents each database contained in
 * the database connection
 *
 * @deprecated Now parts of Datasource interface
 */
interface ITableSetProvider
{

	/**
	 * Provide a database object
	 *
	 * The method should never fail except if the given arguments are invalid
	 * (empty name, invalid class name)
	 *
	 * The method should construct a database object even if the given name
	 * does not exists.
	 *
	 * @param string $a_name
	 * @return Database
	 */
	function getTableSet($a_name);

	/**
	 * Provides an iterator on all the database names of the Datasource
	 *
	 * @return Iterator
	 */
	function getTableSetIterator();

	/**
	 * Check existence of a database name
	 *
	 * @param string $a_databaseName
	 * @return bool
	 */
	function tableSetExists($a_databaseName);

	/**
	 * Set the active/default table set
	 * @param string $name
	 * @return boolean
	 */
	function setActiveTableSet ($name);
}

/**
 * Give access to Database's or Datasource's table
 */
interface ITableProvider
{

	/**
	 *
	 * @return Datasource
	 */
	function getDatasource();

	/**
	 * Create a Table
	 *
	 * @param $a_name
	 * @param $a_alias Set table alias
	 * @param $a_className
	 * @param $a_useAliasAsName Create a 'new' table name @param $a_alias using the same structure as @param $a_name
	 * @return Table
	 */
	function getTable($a_name, $a_alias = null, $a_className = null, $a_useAliasAsName = false);

	/**
	 * Get an iterator on all table names
	 *
	 * @return Iterator
	 */
	function tableIterator();
	
	/**
	 * Query table presence
	 * @param string $a_name
	 *        	Table name
	 * @param integer $a_mode
	 *        	Query mode
	 *        
	 * @return boolean
	 */
	function tableExists($a_name, $a_mode = kObjectQuerySchema);
}

/**
 * Give access to table fields
 */
interface ITableFieldProvider
{

	/**
	 *
	 * @return string
	 */
	function defaultFieldClassName();

	/**
	 *
	 * @return string
	 */
	function defaultStarFieldClassName();

	/**
	 *
	 * @param
	 *        	$a_name
	 * @param
	 *        	$a_alias
	 * @param
	 *        	$a_className
	 * @return TableField
	 */
	function getColumn($a_name, $a_alias = null, $a_className = null);

	/**
	 *
	 * @return Iterator
	 */
	function fieldIterator();

	/**
	 *
	 * @param
	 *        	$a_name
	 * @return bool
	 */
	function columnExists($a_name);
}

?>
