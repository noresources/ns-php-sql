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

/**
 * Implementation of a StatementFactoryInterface using a class map
 */
class ClassMapStatementFactory implements StatementFactoryInterface
{
	use ClassMapStatementFactoryTrait;

	/**
	 *
	 * @param array $overrides
	 *        	Query type -> class name map
	 */
	public function __construct($overrides = array())
	{
		$this->initializeStatementFactory($overrides);
	}
}