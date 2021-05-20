<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement;

use NoreSources\TypeDescription;
use Psr\Container\NotFoundExceptionInterface;

class StatementNotAvailableException extends \Exception implements
	NotFoundExceptionInterface
{

	public function __construct($statementClassname)
	{
		$instruction = \strtoupper(
			\preg_replace('/(?<![A-Z ])([A-Z])/', ' $1',
				\preg_replace('/Query$/', '',
					TypeDescription::getLocalName($statementClassname,
						true))));

		parent::__construct($instruction . ' not available');
	}
}
