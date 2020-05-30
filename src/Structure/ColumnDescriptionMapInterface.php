<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

/**
 * Provide access to a table of column descriptions
 */
interface ColumnDescriptionMapInterface
{

	/**
	 *
	 * @return integer Number of columns
	 */
	function getColumnCount();

	/**
	 * Indicates if a column exists in the map.
	 * Name is case insensitive.
	 *
	 * @param string $name
	 *        	Column name
	 *
	 * @return boolean
	 */
	function hasColumn($name);

	/**
	 * Get a column description by name.
	 * Name is case insensitive but strict case prevails.
	 *
	 * @throws ColumnNotFoundException
	 * @param string $name
	 * @return ColumnDescriptionInterface
	 */
	function getColumn($name);

	/**
	 *
	 * @return \Iterator Column iterator where key is the column name and value is the
	 *         ColumnDescriptionInterface
	 */
	function getColumnIterator();
}