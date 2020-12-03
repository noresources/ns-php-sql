<?php

/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use Psr\Container\NotFoundExceptionInterface;

class EventNotFoundException extends \Exception implements
	NotFoundExceptionInterface
{

	/**
	 *
	 * @param mixed $id
	 *        	Event id
	 */
	public function __construct($id)
	{
		parent::__construct($id . ' not found');
	}
}
