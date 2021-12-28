<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Reference;

use NoreSources\Container\ArrayAccessContainerInterfaceTrait;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\PlatformInterface;
use NoreSources\SQL\DBMS\Configuration\ConfigurationNotAvailableException;
use NoreSources\SQL\DBMS\Configuration\ConfiguratorInterface;
use NoreSources\SQL\DBMS\Traits\ConnectionProviderTrait;
use NoreSources\SQL\DBMS\Traits\PlatformProviderTrait;

/**
 * Reference connection configurator.
 * Does not provide any configuration variable.
 */
class ReferenceConfigurator implements ConfiguratorInterface
{
	use ArrayAccessContainerInterfaceTrait;
	use PlatformProviderTrait;
	use ConnectionProviderTrait;

	public function __construct(PlatformInterface $p,
		ConnectionInterface $c)
	{
		$this->setPlatform($p);
		$this->setConnection($c);
	}

	public function offsetGet($key)
	{
		throw new ConfigurationNotAvailableException(
			$this->getPlatform(), $key);
	}

	public function canSet($key)
	{
		return false;
	}

	public function offsetUnset($key)
	{}

	public function offsetSet($key, $value)
	{
		if (!$this->canSet($key))
			throw new ConfigurationNotAvailableException(
				$this->getPlatform(), $key);
	}
}
