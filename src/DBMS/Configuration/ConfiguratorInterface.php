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
 * Runtime connection configurator.
 *
 * - A single Configurator instance should be used per application.
 * - Configurator implementation may use a caching system.
 *
 * - offsetExists($key), has($key) should be an alias of canGet($key)
 * - offsetGet($key), get($key) Returns the current configuration variable value or throw a
 * ConfigurationNotAvailableException if the configuration variable is not supported.
 * - offsetSet($key, $value) Set the configuration variable to the given value or throw
 * if the configuration variable is not supported.
 * - offsetUnset ($key) Reset the configuration variable to its default value. Unsupported variables
 * are ignored.
 * - ArrayAccess::offsetGet() and ContainerInterface::get() MUST have the same behavior
 * - ArrayAccess::offsetExists() and ContainerInterface::has() MUST have the same behavior
 */
interface ConfiguratorInterface extends \ArrayAccess, ContainerInterface,
	ConnectionProviderInterface
{

	/**
	 * Indicates if the given setting can be read.
	 *
	 * @param string $key
	 *        	Configuration setting
	 * @return boolean
	 */
	function canGet($key);

	/**
	 * Indicates if the given setting can be modified.
	 *
	 * @param string $key
	 *        	Configuration setting
	 * @return boolean
	 */
	function canSet($key);
}
