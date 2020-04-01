<?php

/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

class StructureTraverser
{

	public function __construct($callable, $context = null)
	{
		$this->callable = $callable;
		$this->context = $context;
	}

	public function traverse(StructureElement $structure)
	{
		call_user_func($this->callable, $structure, $this->context);
		foreach ($structure as $name => $child)
		{
			$this->traverse($child);
		}
	}

	/**
	 *
	 * @var callable
	 */
	private $callable;

	/**
	 *
	 * @var mixed
	 */
	private $context;
}