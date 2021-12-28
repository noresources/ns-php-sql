<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\Container\ArrayAccessContainerInterfaceTrait;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\PlatformInterface;
use NoreSources\SQL\DBMS\Configuration\ConfigurationNotAvailableException;
use NoreSources\SQL\DBMS\Configuration\ConfiguratorInterface;
use NoreSources\SQL\DBMS\Configuration\ConfiguratorTrait;
use NoreSources\SQL\DBMS\Traits\ConnectionProviderTrait;
use NoreSources\SQL\DBMS\Traits\PlatformProviderTrait;
use NoreSources\SQL\Result\Recordset;
use NoreSources\Type\TypeConversion;

class SQLiteConfigurator implements ConfiguratorInterface
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
		if (($value = $this->getCachedValue($key)) !== null)
			return $value;

		if ($key == K::CONFIGURATION_TIMEZONE)
			return $this->setCachedValue($key,
				$this->normalizeValue($key, 'UTC'));
		elseif ($key == K::CONFIGURATION_KEY_CONSTRAINTS)
		{
			$fk = TypeConversion::toInteger(
				$this->getPragma('foreign_keys'));
			$ic = TypeConversion::toInteger(
				$this->getPragma('ignore_check_constraints'));
			return $this->setCachedValue($key, ($fk != 0) && ($ic == 0));
		}
		elseif ($key == K::CONFIGURATION_SUBMIT_TIMEOUT)
		{
			return $this->setCachedValue($key,
				$this->normalizeValue($key,
					$this->getPragma('busy_timeout')));
		}

		throw new ConfigurationNotAvailableException(
			$this->getPlatform(), $key);
	}

	public function canSet($key)
	{
		switch ($key)
		{
			case K::CONFIGURATION_KEY_CONSTRAINTS:
			case K::CONFIGURATION_SUBMIT_TIMEOUT:
				return true;
		}

		return false;
	}

	public function canGet($key)
	{
		switch ($key)
		{
			case K::CONFIGURATION_KEY_CONSTRAINTS:
			case K::CONFIGURATION_SUBMIT_TIMEOUT:
			case K::CONFIGURATION_TIMEZONE:
				return true;
		}

		return false;
	}

	public function offsetUnset($key)
	{
		$this->unsetCachedValue($key);
		if ($key == K::CONFIGURATION_KEY_CONSTRAINTS)
		{
			$this->setPragma('foreign_keys',
				SQLiteConnection::CONFIGURATION_FOREIGN_KEY_CONSTRAINTS_DEFAULT);
			$this->setPragma('ignore_check_constraints', 0);
		}
		elseif ($key == K::CONFIGURATION_SUBMIT_TIMEOUT)
		{
			$this->setPragma('busy_timeout',
				SQLiteConnection::CONFIGURATION_SUBMIT_TIMEOUT_DEFAULT);
		}
	}

	public function offsetSet($key, $value)
	{
		if (!$this->canSet($key))
			throw new ConfigurationNotAvailableException(
				$this->getPlatform(), $key);
		$this->unsetCachedValue($key);

		$value = $this->normalizeValue($key, $value);
		$this->setCachedValue($key, $value);

		if ($key == K::CONFIGURATION_KEY_CONSTRAINTS)
		{
			$value = ($value ? 1 : 0);
			$this->setPragma('foreign_keys', $value);
			$this->setPragma('ignore_check_constraints', $value ^ 1);
		}
		elseif ($key == K::CONFIGURATION_SUBMIT_TIMEOUT)
		{
			$this->setPragma('busy_timeout', $value);
		}
	}

	public function getPragma($key)
	{
		return Recordset::columnValue(
			$this->getConnection()->executeStatement('PRAGMA ' . $key));
	}

	public function setPragma($key, $value)
	{
		if (\is_string($value))
			$value = $this->getPlatform()->quoteStringValue($value);
		if (\is_bool($value))
			$value = ($value ? 1 : 0);
		$this->getConnection()->executeStatement(
			'PRAGMA ' . $key . '=' . $value);
	}
}
