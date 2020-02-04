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

interface ColumnPropertyMap
{

	/**
	 *
	 * @param string $key
	 */
	function hasColumnProperty($key);

	/**
	 *
	 * @param string $key
	 * @return boolean
	 */
	function getColumnProperty($key);

	/**
	 * Get all column properties
	 * #return \Traversable
	 */
	function getColumnProperties();

	/**
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	function setColumnProperty($key, $value);

	/**
	 * Remove a column property
	 *
	 * @param string $key
	 */
	function removeColumnProperty($key);
}
