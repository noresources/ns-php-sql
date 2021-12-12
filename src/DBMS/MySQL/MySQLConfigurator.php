<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\Container\ArrayAccessContainerInterfaceTrait;
use NoreSources\Container\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\PlatformInterface;
use NoreSources\SQL\DBMS\Configuration\ConfigurationNotAvailableException;
use NoreSources\SQL\DBMS\Configuration\ConfiguratorInterface;
use NoreSources\SQL\DBMS\Traits\ConnectionProviderTrait;
use NoreSources\SQL\DBMS\Traits\PlatformProviderTrait;
use NoreSources\SQL\Result\Recordset;

class MySQLConfigurator implements ConfiguratorInterface
{
	use ArrayAccessContainerInterfaceTrait;
	use ConnectionProviderTrait;
	use PlatformProviderTrait;

	public function __construct(PlatformInterface $platform,
		ConnectionInterface $connection)
	{
		$this->setConnection($connection);
		$this->setPlatform($platform);
	}

	public function offsetGet($key)
	{
		if ($key == K::CONFIGURATION_KEY_CONSTRAINTS)
		{
			$value = Recordset::columnValue(
				$this->getConnection()->executeStatement(
					'SELECT @@SESSION.foreign_key_checks'));
			return ($value != 0);
		}
		throw new ConfigurationNotAvailableException(
			$this->getPlatform(), $key);
	}

	public function offsetExists($key)
	{
		static $supported = [
			K::CONFIGURATION_KEY_CONSTRAINTS
		];
		return Container::valueExists($supported, $key);
	}

	public function offsetUnset($key)
	{
		if ($key == K::CONFIGURATION_KEY_CONSTRAINTS)
		{
			$this->getConnection()->executeStatement(
				'SET foreign_key_checks=(SELECT @@GLOBAL.foreign_key_checks)');
		}
	}

	public function offsetSet($key, $value)
	{
		if ($key == K::CONFIGURATION_KEY_CONSTRAINTS)
		{
			$value = ($value ? 1 : 0);
			$this->getConnection()->executeStatement(
				'SET foreign_key_checks=' . $value);
			return;
		}

		throw new ConfigurationNotAvailableException(
			$this->getPlatform(), $key);
	}
}
