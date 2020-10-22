<?php
namespace NoreSources\SQL\DBMS;

/**
 * Provider interface for class exposing a PlatformInterface
 */
interface PlatformProviderInterface
{

	/**
	 *
	 * @return PlatformInterface
	 */
	function getPlatform();
}
