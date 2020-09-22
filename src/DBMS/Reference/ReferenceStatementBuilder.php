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
use NoreSources\SQL\Statement\ParameterData;

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
			$this->platform = new ReferencePlatform();

		$this->initializeStatementFactory();
	}

	public static function serializeStringFallback($value)
	{
		return "'" . str_replace("'", "''", $value) . "'";
	}

	public function serializeString($value)
	{
		return self::serializeStringFallback($value);
	}

	public static function escapeIdentifierFallback($identifier, $before,
		$after)
	{
		$identifier = \str_replace($before, $before . $before,
			$identifier);
		if ($before != $after)
			$identifier = \str_replace($after, $after . $after,
				$identifier);
		return $before . $identifier . $after;
	}

	public function escapeIdentifier($identifier)
	{
		return self::escapeIdentifierFallback($identifier, '[', ']');
	}

	public function getParameter($name, ParameterData $parameters = null)
	{
		return ('$' . preg_replace('/[^a-zA-Z0-9_]/', '_', $name));
	}
}