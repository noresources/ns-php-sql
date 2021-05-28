<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Configuration;

use NoreSources\SQL\DBMS\ConnectionProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * Runtime connection configurator
 *
 * - offsetExists($key) Returns TRUE if the configuration variable is available on the given
 * platform, FALSE otherwise.
 * - offsetGet($key) Returns the current configuration variable value or throw a
 * ConfigurationNotAvailableException if the configuration variable is not supported.
 * - offsetSet($key, $value) Set the configuration variable to the given value or throw
 * ConfigurationNotAvailableException if the configuration variable is not supported.
 * - offsetUnset ($key) Reset the configuration variable to its default value. Unsupported variables
 * are ignored.
 * - ArrayAccess::offsetGet() and ContainerInterface::get() MUST have the same behavior
 * - ArrayAccess::offsetExists() and ContainerInterface::has() MUST have the same behavior
 */
interface ConfiguratorInterface extends \ArrayAccess, ContainerInterface,
	ConnectionProviderInterface
{
}
