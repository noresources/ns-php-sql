<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Reference;

use NoreSources\ArrayAccessContainerInterfaceTrait;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\PlatformInterface;
use NoreSources\SQL\DBMS\Configuration\ConfigurationNotAvailableException;
use NoreSources\SQL\DBMS\Configuration\ConfiguratorInterface;
use NoreSources\SQL\DBMS\Traits\ConnectionProviderTrait;
use NoreSources\SQL\DBMS\Traits\PlatformProviderTrait;

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

	public function offsetExists($key)
	{
		return false;
	}

	public function offsetUnset($key)
	{}

	public function offsetSet($key, $value)
	{
		throw new ConfigurationNotAvailableException(
			$this->getPlatform(), $key);
	}
}
