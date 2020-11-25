<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement;

/**
 * Describe statements parameters
 */
interface ParameterDataProviderInterface
{

	/**
	 *
	 * @return ParameterData The statement parameters
	 */
	function getParameters();
}