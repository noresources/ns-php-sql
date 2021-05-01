<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

/**
 * Provider interface for classes exposing a ConnectionInterface
 */
interface ConnectionProviderInterface
{

	/**
	 *
	 * @return ConnectionInterface
	 */
	function getConnection();
}

