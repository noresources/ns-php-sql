<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Traits;

use NoreSources\SQL\DBMS\PlatformInterface;

trait PlatformProviderTrait
{

	/**
	 *
	 * @return \NoreSources\SQL\DBMS\PlatformInterface
	 */
	public function getPlatform()
	{
		return $this->platform;
	}

	/**
	 *
	 * @param PlatformInterface $platform
	 */
	protected function setPlatform(PlatformInterface $platform)
	{
		$this->platform = $platform;
	}

	/**
	 *
	 * @var PlatformInterface
	 */
	private $platform;
}
