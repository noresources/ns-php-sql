<?php

/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

class StructureSerializationException extends \Exception
{

	public function __construct($message)
	{
		parent::__construct($message);
	}
}

