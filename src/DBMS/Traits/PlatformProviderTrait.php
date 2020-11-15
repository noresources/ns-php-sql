<?php
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
	 * @var PlatformInterface
	 */
	protected $platform;
}
