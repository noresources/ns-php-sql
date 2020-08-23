<?php
use NoreSources\Container;
use NoreSources\Stack;
use NoreSources\SQL\DBMS\ConnectionException;
use NoreSources\SQL\DBMS\ConnectionFactoryInterface;
use NoreSources\SQL\DBMS\ConnectionInterface;

/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\Container;
use NoreSources\Stack;

/**
 */
class ConnectionFactoryStack implements ConnectionFactoryInterface
{

	public function __construct()
	{
		$this->factoryStack = new Stack();
	}

	/**
	 *
	 * @param ConnectionFactoryInterface $factory
	 * @return ConnectionFactoryStack
	 */
	public function pushFactory(ConnectionFactoryInterface $factory)
	{
		$this->factoryStack->push($factory);
		return $this;
	}

	public function createConnection($settings = [])
	{
		$connection = null;
		$exceptions = [];
		foreach ($this->factoryStack as $factory)
		{
			try
			{
				$connection = $factory->createConnection($settings);
			}
			catch (\Exception $e)
			{
				$exceptions[] = e;
			}

			if ($connection instanceof ConnectionInterface)
				break;
		}

		if ($connection instanceof ConnectionInterface)
			return $connection;

		$message = 'Failed to create connection using ' .
			Container::implodeValues($this->factoryStack, [
				Container::IMPLODE_BETWEEN => ', '
				Container::IMPLODE_BETWEEN_LAST => ' and '
			], function ($f) {
				return TypeDescription::getLocalName($f);
			});

		if (\count($exceptions))
		{
			$message .= ': ';
			$message = Container::implodeValues($exceptions, '.' . PHP_EOL,
				function ($e) {
					return $e->getMessage();
				});
		}

		throw new ConnectionException($message);
	}

	public function __invoke()
	{
		if (func_num_args() != 1)
			throw new \BadMethodCallException('Missing settings argument');
		$arg = func_get_arg(0);
		if (!\is_array($arg))
			throw new \BadMethodCallException('Array argument expected');

		return $this->createConnection($arg);
	}

	/**
	 *
	 * @var Stack
	 */
	private $factoryStack;
}