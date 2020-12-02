<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use Psr\Container\NotFoundExceptionInterface;

class ColumnPropertyNotFoundException extends \InvalidArgumentException implements
	NotFoundExceptionInterface
{

	/**
	 *
	 * @param string $property
	 *        	Property ID
	 */
	public function __construct($property)
	{
		parent::__construct($property . ' property not found', 404);
	}
}
