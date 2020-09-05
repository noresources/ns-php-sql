<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

namespace NoreSources\SQL\Statement;

/**
 * Statement information about parameters etc.
 */
interface StatementInputDataInterface extends ParameterDataProviderInterface
{

	/**
	 * Initialize InputDataTrait members.
	 * This method have to be called in constructors of
	 * class that use this trait.
	 *
	 * @param StatementInputDataInterface $data
	 */
	function initializeInputData(StatementInputDataInterface $data = null);
}
