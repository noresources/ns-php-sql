<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\ComparableInterface;
use NoreSources\Container\Container;
use NoreSources\SQL\NameProviderInterface;
use NoreSources\Type\ArrayRepresentation;
use NoreSources\Type\StringRepresentation;
use NoreSources\Type\TypeConversion;

/**
 * Structure element path or alias
 */
class Identifier implements StringRepresentation, ArrayRepresentation,
	ComparableInterface
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
			return $path->getIdentifier();
		if ($path instanceof Identifier)
			return $path;
		if ($path instanceof NameProviderInterface)
			return new Identifier($path->getName());
		if (Container::isTraversable($path))
			return new Identifier(Container::implodeValues($path, '.'));

		return new Identifier(TypeConversion::toString($path));
	}

	/**
	 * Generate a unique identifier
	 *
	 * @param number $length
	 *        	Identifier string length
	 * @return string Unique identifier
	 */
	public static function generate($length = 32)
	{
		$id = '';
		do
		{
			$id .= \uniqid('', true);
		}
		while (\strlen($id) < $length + 2);
		return \substr(\strrev(\base64_encode($id)), 2, $length);
	}

	/**
	 *
	 * @param string $path
	 *        	Dot-separated structure path
	 */
	public function __construct($path)
	{
		$this->path = \strval($path);
	}

	public function __toString()
	{
		return $this->getPath();
	}

	/**
	 *
	 * @return boolean TRUE if identifier path is empty
	 */
	public function isEmpty()
	{
		return empty($this->path);
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

	public function compare($value)
	{
		$value = self::make($value);
		return \strcmp(\strval($this), \strval($value));
	}

	/**
	 *
	 * @var string Dot-separated structure path
	 */
	protected $path;
}