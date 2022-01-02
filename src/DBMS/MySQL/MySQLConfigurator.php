<?php
/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\DateTime;
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

class MySQLConfigurator implements ConfiguratorInterface
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
		$setting = $this->getMySQLServerSettingName($key);
		if ($setting === false)
			throw new ConfigurationNotAvailableException(
				$this->getPlatform(), $key);

		if (($value = $this->getCachedValue($key)) !== null)
			return $value;

		$value = Recordset::columnValue(
			$this->getConnection()->executeStatement(
				'SELECT @@SESSION.' . $setting));

		if ($key == K::CONFIGURATION_TIMEZONE)
		{
			if ((\strcasecmp($value, 'system') == 0))
				$value = \date_default_timezone_get();
			elseif (\preg_match('/[+-][0-9]{2}:[0-9]{2}/', $value))
			{
				$dt = DateTime::createFromFormat('O', $value);
				$value = $dt->getTimezone();
			}
		}

		return $this->setCachedValue($key,
			$this->normalizeValue($key, $value));
	}

	public function canSet($key)
	{
		$setting = $this->getMySQLServerSettingName($key);
		return ($setting !== false);
	}

	public function offsetUnset($key)
	{
		$setting = $this->getMySQLServerSettingName($key);
		if ($setting === false)
			return;
		$this->getConnection()->executeStatement(
			'SET @@SESSION.' . $setting . '=(SELECT @@GLOBAL.' . $setting .
			')');
		$this->unsetCachedValue($key);
	}

	public function offsetSet($key, $value)
	{
		$setting = $this->getMySQLServerSettingName($key);
		if ($setting === false)
			throw new ConfigurationNotAvailableException(
				$this->getPlatform(), $key);

		$this->unsetCachedValue($key);
		$value = $this->normalizeValue($key, $value);
		$settingValue = $value;
		$settingDataType = K::DATATYPE_STRING;
		if ($key == K::CONFIGURATION_KEY_CONSTRAINTS)
		{
			$settingValue = ($value ? 1 : 0);
			$settingDataType = K::DATATYPE_INTEGER;
		}
		elseif ($key == K::CONFIGURATION_TIMEZONE)
		{
			$now = new DateTime('now', $value);
			$settingValue = $now->format('P');
		}

		$this->getConnection()->executeStatement(
			'SET @@SESSION.' . $setting . '=' .
			$this->getPlatform()
				->serializeData($settingValue, $settingDataType));
		$this->setCachedValue($key, $value);
	}

	public function getMySQLServerSettingName($key)
	{
		static $settings = [
			K::CONFIGURATION_TIMEZONE => 'time_zone',
			K::CONFIGURATION_KEY_CONSTRAINTS => 'foreign_key_checks'
		];
		return Container::keyValue($settings, $key, false);
	}
}
