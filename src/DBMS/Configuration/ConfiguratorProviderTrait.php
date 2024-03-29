<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Configuration;

/**
 * Default implementation of ConfiguratorProviderInterface
 * for ConnectionInterface.
 */
trait ConfiguratorProviderTrait
{

	/**
	 *
	 * @return ConfiguratorInterface
	 */
	public function getConfigurator()
	{
		if (!isset($this->configurator))
		{
			$platform = $this->getPlatform();
			$this->configurator = $platform->newConfigurator($this);
		}

		return $this->configurator;
	}

	/**
	 *
	 * @varConfiguratorInterface
	 */
	private $configurator;
}
