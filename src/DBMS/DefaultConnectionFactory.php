<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;

/**
 * Create one of the built-in ConnectionInterface implementations
 */
class DefaultConnectionFactory implements ConnectionFactoryInterface
{

	/**
	 *
	 * @param $settings array
	 *        	Connection settings.
	 *        	CONNECTION_TYPE could be the ConnectionInterface class name or
	 *        	the DMBS implementation source directory name
	 */
	public function createConnection($settings = array())
	{
		if (!Container::isArray($settings))
			$settings = [
				K::CONNECTION_TYPE => $settings
			];

		$type = Container::keyValue($settings, K::CONNECTION_TYPE,
			'Reference');
		$className = null;

		$classNames = [
			$type,
			__NAMESPACE__ . '\\' . $type . '\\' . $type . 'Connection'
		];

		foreach ($classNames as $className)
		{
			if (\class_exists($className) &&
				\is_subclass_of($className, ConnectionInterface::class,
					true))
			{
				$cls = new \ReflectionClass($className);
				return $cls->newInstance($settings);
			}
		}

		throw new \InvalidArgumentException(
			'Unable to create a ConnectionInterface using classes ' .
			\implode(', ', $classNames));

		return $connection;
	}

	public function __invoke()
	{
		if (func_num_args() != 1)
			throw new \BadMethodCallException(
				'Missing settings argument');
		$arg = func_get_arg(0);
		if (!\is_array($arg))
			throw new \BadMethodCallException('Array argument expected');

		return $this->createConnection($arg);
	}
}