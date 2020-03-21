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
 * Describe statements parameters
 */
interface ParametrizedStatement
{

	/**
	 *
	 * @return ParameterData The statement parameters
	 */
	function getParameters();
}