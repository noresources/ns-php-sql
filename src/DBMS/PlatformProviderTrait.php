<?php
namespace NoreSources\SQL\DBMS;

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
