<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Filesystem;

use NoreSources\Container\Container;

class ClosureStructureFilenameFactory implements
	StructureFilenameFactoryInterface
{

	public function __construct($callable)
	{
		if (!\is_callable($callable) && Container::isArray($callable))
			$callable = Container::createArray($callable);

		if (!\is_callable($callable))
			throw new \InvalidArgumentException('Not callable');

		$this->callable = $callable;
	}

	public function buildStructureFilename($identifier, $type = null)
	{
		return \call_user_func($this->callable, $identifier, $type);
	}

	/**
	 *
	 * @var callable
	 */
	private $callable;
}
