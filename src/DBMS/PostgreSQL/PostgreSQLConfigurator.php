<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
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
use NoreSources\SQL\DBMS\Configuration\ConfiguratorTrait;
use NoreSources\SQL\DBMS\Traits\ConnectionProviderTrait;
use NoreSources\SQL\DBMS\Traits\PlatformProviderTrait;
use NoreSources\SQL\Result\Recordset;

class PostgreSQLConfigurator implements ConfiguratorInterface
{
	use ArrayAccessContainerInterfaceTrait;
	use ConnectionProviderTrait;
	use PlatformProviderTrait;
	use ConfiguratorTrait;

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
			if (($value = $this->getCachedValue($key)) !== null)
				return $value;
			$value = $this->show('session_replication_role');
			$value = ($value != 'replica');

			$this->setCachedValue($key, $value);
			return $value;
		}
		elseif ($key == K::CONFIGURATION_TIMEZONE)
		{
			if (($value = $this->getCachedValue($key)) !== null)
				return $value;
			$value = $this->show('timezone');
			$value = $this->normalizeValue($key, $value);
			$this->setCachedValue($key, $value);
			return $value;
		}

		throw new ConfigurationNotAvailableException(
			$this->getPlatform(), $key);
	}

	public function canSet($key)
	{
		static $supported = [
			K::CONFIGURATION_KEY_CONSTRAINTS,
			K::CONFIGURATION_TIMEZONE
		];
		return Container::valueExists($supported, $key);
	}

	public function offsetUnset($key)
	{
		if ($key == K::CONFIGURATION_KEY_CONSTRAINTS)
		{
			$this->set('session_replication_role', 'origin');
			$this->unsetCachedValue($key);
		}
	}

	public function offsetSet($key, $value)
	{
		if (!$this->canSet($key))
			throw new ConfigurationNotAvailableException(
				$this->getPlatform(), $key);

		$this->unsetCachedValue($key);

		$value = $this->normalizeValue($key, $value);
		if ($key == K::CONFIGURATION_KEY_CONSTRAINTS)
		{
			$this->set('session_replication_role',
				($value ? 'origin' : 'replica'));
		}
		elseif ($key == K::CONFIGURATION_TIMEZONE)
		{
			/** @var \DateTimeZone $value */
			$this->set('timezone', $value->getName());
		}
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
