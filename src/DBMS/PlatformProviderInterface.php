<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

/**
 * Provider interface for class exposing a PlatformInterface
 */
interface PlatformProviderInterface
{

	/**
	 *
	 * @return PlatformInterface
	 */
	function getPlatform();
}
