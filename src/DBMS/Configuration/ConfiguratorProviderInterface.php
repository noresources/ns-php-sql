<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Configuration;

interface ConfiguratorProviderInterface
{

	/**
	 *
	 * @return ConfiguratorInterface
	 */
	function getConfigurator();
}
