<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Reference;

use NoreSources\SQL\DBMS\PlatformInterface;
use NoreSources\SQL\DBMS\PlatformProviderTrait;
use NoreSources\SQL\Statement\AbstractStatementBuilder;
use NoreSources\SQL\Statement\ClassMapStatementFactoryTrait;

/**
 */
class ReferenceStatementBuilder extends AbstractStatementBuilder
{
	use ClassMapStatementFactoryTrait;
	use PlatformProviderTrait;

	/**
	 * Builder flags for each builder domain
	 */
	public function __construct(PlatformInterface $platform = null)
	{
		parent::__construct();
		$this->platform = $platform;
		if (!($this->platform instanceof PlatformInterface))
			$this->platform = new ReferencePlatform(
				new ReferenceConnection());

		$this->initializeStatementFactory();
	}

	public static function serializeStringFallback($value)
	{
		return "'" . str_replace("'", "''", $value) . "'";
	}
}