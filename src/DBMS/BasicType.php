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

use NoreSources\SQL\Constants as K;

/**
 * Name-only DBMS type
 */
class BasicType implements TypeInterface
{

	/**
	 *
	 * @param string $typeName
	 */
	public function __construct($typeName)
	{
		$this->typeName = $typeName;
	}

	public function getTypeName()
	{
		return $this->typeName;
	}

	public function has($id)
	{
		return ($id == K::TYPE_PROPERTY_NAME);
	}

	public function get($id)
	{
		if ($id == self::TYPE_PROPERTY_NAME)
			return $this->typeName;

		throw new TypePropertyNotFoundException($this, $id);
	}

	private $typeName;
}