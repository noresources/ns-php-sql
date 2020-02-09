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

use Psr\Container\NotFoundExceptionInterface;

class ParameterNotFoundException implements NotFoundExceptionInterface
{

	public function __construct($key)
	{
		parent::__construct($key . ' parameter not found');
	}
}