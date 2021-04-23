<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\ArrayRepresentation;
use NoreSources\Container;
use NoreSources\StringRepresentation;
use NoreSources\TypeConversion;
use NoreSources\SQL\NameProviderInterface;

/**
 * Structure element path or alias
 */
class Identifier implements StringRepresentation,
	ArrayRepresentation
{

	/**
	 * Transform input to a Identifier
	 *
	 * @param mixed $path
	 * @return Identifier. If $path is already a Identifier, $path
	 *         is returned unchanged.
	 */
	public static function make($path)
	{
		if (empty($path))
			return new Identifier('');
		if ($path instanceof StructureElementInterface)
			return new Identifier($path->getPath());
		if ($path instanceof Identifier)
			return $path;
		if (Container::isTraversable($path))
			return new Identifier(
				Container::implodeValues($path, '.'));
		if ($path instanceof NameProviderInterface)
			return new Identifier($path->getName());

		return new Identifier(
			TypeConversion::toString($path));
	}

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
		return $this->getPath();
	}

	/**
	 * String representation of the identifier path
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	public function getArrayCopy()
	{
		return $this->getPathParts();
	}

	/**
	 * Get path as a list of identifiers
	 *
	 * @return array
	 */
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

	/**
	 * Get parent identifier
	 *
	 * @return Identifier|NULL Parent identifier if any, otherwise NULL.
	 */
	public function getParentIdentifier()
	{
		$a = $this->getArrayCopy();
		if (\count($a) == 0)
			return null;
		\array_pop($a);
		return Identifier::make($a);
	}

	/**
	 *
	 * @param string $name
	 * @return $this
	 */
	public function append($name)
	{
		if (!empty($this->path))
			$this->path .= '.';

		$this->path .= $name;

		return $this;
	}

	/**
	 *
	 * @var string Dot-separated structure path
	 */
	protected $path;
}