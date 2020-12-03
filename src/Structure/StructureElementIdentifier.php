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
class StructureElementIdentifier implements StringRepresentation,
	ArrayRepresentation
{

	/**
	 * Transform input to a StructureElementIdentifier
	 *
	 * @param mixed $path
	 * @return StructureElementIdentifier. If $path is already a StructureElementIdentifier, $path
	 *         is returned unchanged.
	 */
	public static function make($path)
	{
		if (empty($path))
			return new StructureElementIdentifier('');
		if ($path instanceof StructureElementInterface)
			return new StructureElementIdentifier($path->getPath());
		if ($path instanceof StructureElementIdentifier)
			return $path;
		if (Container::isTraversable($path))
			return new StructureElementIdentifier(
				Container::implodeValues($path, '.'));
		if ($path instanceof NameProviderInterface)
			return new StructureElementIdentifier($path->getName());

		return new StructureElementIdentifier(
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
	 * @return StructureElementIdentifier|NULL Parent identifier if any, otherwise NULL.
	 */
	public function getParentIdentifier()
	{
		$a = $this->getArrayCopy();
		if (\count($a) == 0)
			return null;
		\array_pop($a);
		return StructureElementIdentifier::make($a);
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