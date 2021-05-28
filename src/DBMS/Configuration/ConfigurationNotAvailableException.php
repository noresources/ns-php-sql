<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Configuration;

use NoreSources\TypeDescription;
use NoreSources\SQL\DBMS\PlatformInterface;
use Psr\Container\NotFoundExceptionInterface;

class ConfigurationNotAvailableException extends \Exception implements
	NotFoundExceptionInterface
{

	/**
	 *
	 * @param PlatformInterface $platform
	 * @param string $key
	 */
	public function __construct(PlatformInterface $platform, $key)
	{
		$message = $key . ' is not available on ' .
			TypeDescription::getLocalName($platform);
		parent::__construct($message);
	}
}
