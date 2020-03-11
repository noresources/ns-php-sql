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
 * Generic, partial implementation of StatementBuilderInterface.
 *
 * This should be used as base class for all DBMS-specific statement builders.
 */
abstract class StatementBuilder implements StatementBuilderInterface
{

	use StatementBuilderTrait;

	/**
	 *
	 * @param number $flags
	 *        	StatementBuilder flags
	 */
	public function __construct()
	{
		$this->initializeStatementBuilderTrait();
	}
}
