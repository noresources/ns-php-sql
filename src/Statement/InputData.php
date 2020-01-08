<?php
/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

// Namespace
namespace NoreSources\SQL\Statement;

/**
 * Statement information about parameters etc.
 */
interface InputData
{

	/**
	 *
	 * @return integer
	 */
	function getNamedParameterCount();

	/**
	 *
	 * @return integer Total number of parameter occurences
	 */
	function getParameterCount();

	/**
	 *
	 * @param integer|string $key
	 *        	Parameter position or name
	 * @return boolean
	 */
	function hasParameter($key);

	/**
	 *
	 * @param integer|string $key
	 *        	Parameter name or index
	 * @return string DBMS representation of the parameter name
	 */
	function getParameter($key);

	/**
	 *
	 * @return ParameterMap
	 */
	function getParameters();

	/**
	 *
	 * @param integer $position
	 *        	Parameter position in the statement
	 * @param string $key
	 *        	Parameter name
	 * @param string $dbmsName
	 *        	DBMS representation of the parameter name
	 */
	function registerParameter($position, $key, $dbmsName);

	function initializeInputData(InputData $data = null);
}
