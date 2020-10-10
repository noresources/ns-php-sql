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

use NoreSources\StringRepresentation;

/**
 * Structure element path or alias
 */
class StructureElementIdentifier implements StringRepresentation
{

	/**
	 *
	 * @var string Dot-separated structure path
	 */
	public $path;

	/**
	 *
	 * @param string $path
	 *        	Dot-separated structure path
	 */
	public function __construct($path)
	{
		$this->path = $path;
	}

	public function __toString()
	{
		return $this->path;
	}

	public function getPathParts()
	{
		return \explode('.', $this->path);
	}

	/**
	 *
	 * @return string Element local name
	 */
	public function getLocalName()
	{
		$x = \explode('.', $this->path);
		return $x[\count($x) - 1];
	}
}