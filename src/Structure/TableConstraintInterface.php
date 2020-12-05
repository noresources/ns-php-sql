<?php

/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\NameProviderInterface;

/**
 * Table constraint interface
 */
interface TableConstraintInterface extends NameProviderInterface
{

	/**
	 *
	 * @return integer
	 */
	function getConstraintFlags();
}
