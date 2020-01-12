<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Expression;

/**
 * Structure element path or alias
 */
abstract class StructureElementIdentifier implements Expression
{

	public $path;

	public function __construct($path)
	{
		$this->path = $path;
	}
}