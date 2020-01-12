<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

// Namespace
namespace NoreSources\SQL\Statement;

/**
 * Statement building data
 */
interface OutputData
{

	/**
	 *
	 * @return integer
	 */
	function getStatementType();

	/**
	 *
	 * @return integer
	 */
	function getResultColumnCount();

	/**
	 *
	 * @param string $key
	 * @return ResultColumn
	 */
	function getResultColumn($key);

	/**
	 *
	 * @return ResultColumnMap
	 */
	function getResultColumns();

	/**
	 *
	 * @return \ArrayIterator
	 */
	function getResultColumnIterator();

	/**
	 *
	 * @param Statement\OutputData $data
	 */
	function initializeOutputData($data = null);
}

