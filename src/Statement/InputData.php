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
 * Statement information about parameters etc.
 */
interface InputData extends ParametrizedStatement
{

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

	/**
	 * Initialize InputDataTrait members.
	 * This method have to be called in constructors of
	 * class that use this trait.
	 *
	 * @param InputData $data
	 */
	function initializeInputData(InputData $data = null);
}
