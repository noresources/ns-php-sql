<?php
namespace NoreSources\SQL\DBMS;

interface PlatformProviderInterface
{

	/**
	 *
	 * @return PlatformInterface
	 */
	function getPlatform();
}
