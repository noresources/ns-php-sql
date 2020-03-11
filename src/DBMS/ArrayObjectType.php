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

use NoreSources\ArrayRepresentation;
use NoreSources\SQL\Constants as K;

class ArrayObjectType implements TypeInterface, ArrayRepresentation
{

	/**
	 *
	 * @param \ArrayObject|string|array $properties
	 */
	public function __construct($properties)
	{
		if ($properties instanceof \ArrayObject)
			$this->typeProperties = $properties;
		elseif (\is_string($properties))
			$this->typeProperties = new \ArrayObject([
				K::TYPE_PROPERTY_NAME => $properties
			]);
		else
			$this->typeProperties = new \ArrayObject($properties);
	}

	public function __tostring()
	{
		return $this->getTypeName();
	}

	public function getArrayCopy()
	{
		return $this->typeProperties->getArrayCopy();
	}

	public function getTypeName()
	{
		return $this->typeProperties->offsetGet(K::TYPE_PROPERTY_NAME);
	}

	public function has($id)
	{
		return $this->typeProperties->offsetExists($id);
	}

	public function get($id)
	{
		if (!$this->typeProperties->offsetExists($id))
			throw new TypePropertyNotFoundException($this, $id);

		return $this->typeProperties->offsetGet($id);
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $typeProperties;
}