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

class ClassMapStatementFactory implements StatementFactoryInterface
{
	use ClassMapStatementFactoryTrait;

	public function __construct($overrides = array())
	{
		$this->initializeStatementFactory($overrides);
	}
}