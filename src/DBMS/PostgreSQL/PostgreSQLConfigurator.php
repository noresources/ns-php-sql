<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\PostgreSQL;

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

class PostgreSQLConfigurator implements ConfiguratorInterface
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
			$value = $this->show('session_replication_role');
			return ($value != 'replica');
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
			$this->set('session_replication_role', 'origin');
		}
	}

	public function offsetSet($key, $value)
	{
		if ($key == K::CONFIGURATION_KEY_CONSTRAINTS)
		{
			$value = ($value ? 'origin' : 'replica');
			$this->set('session_replication_role', $value);
			return;
		}

		throw new ConfigurationNotAvailableException(
			$this->getPlatform(), $key);
	}

	public function show($key)
	{
		$result = $this->getConnection()->executeStatement(
			'SHOW ' . $key);

		return Recordset::columnValue($result);
	}

	public function set($key, $value)
	{
		if (\is_string($value))
			$value = $this->getPlatform()->quoteStringValue($value);
		if (\is_bool($value))
			$value = ($value ? 1 : 0);
		$this->getConnection()->executeStatement(
			'SET ' . $key . '=' . $value);
	}
}
