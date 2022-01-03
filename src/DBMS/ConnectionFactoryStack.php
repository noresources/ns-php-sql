<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\Container\Container;
use NoreSources\Container\Stack;
use NoreSources\Type\TypeDescription;

/**
 * ConnectionFactory composed of a stack of sub Connection factories.
 *
 * Factories are called iteratively until a ConnectionInterface is created successfully.
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
		if ($this->factoryStack->isEmpty())
			throw new \RuntimeException('Empty connection stack');

		$exceptions = [];
		foreach ($this->factoryStack as $factory)
		{
			try
			{
				return $factory->createConnection($settings);
			}
			catch (\Exception $e)
			{
				$exceptions[] = $e;
			}
		}

		$message = 'Failed to create connection using ' .
			Container::implodeValues($this->factoryStack,
				[
					Container::IMPLODE_BETWEEN => ', ',
					Container::IMPLODE_BETWEEN_LAST => ' and '
				],
				function ($f) {
					return TypeDescription::getLocalName($f);
				});

		if (\count($exceptions))
		{
			$message .= ': ';
			$message = Container::implodeValues($exceptions,
				'.' . PHP_EOL,
				function ($e) {
					return $e->getMessage();
				});
		}

		throw new \RuntimeException($message);
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

	/**
	 *
	 * @var Stack
	 */
	private $factoryStack;
}