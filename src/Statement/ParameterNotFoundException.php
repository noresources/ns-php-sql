<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

// 
namespace NoreSources\SQL\Statement;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception raised while attempting to a parameter key which does not exists
 */
class ParameterNotFoundException extends \Exception implements NotFoundExceptionInterface
{

	public function __construct($key)
	{
		parent::__construct($key . ' parameter not found');
	}
}