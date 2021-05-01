<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Types;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ItemNotFoundException;

/**
 * Name-only DBMS type
 */
class BasicType extends AbstractType
{

	/**
	 *
	 * @param string $typeName
	 */
	public function __construct($typeName)
	{
		$this->typeName = $typeName;
	}

	public function __toString()
	{
		return $this->getTypeName();
	}

	public function getArrayCopy()
	{
		return [
			K::TYPE_NAME => $this->typeName
		];
	}

	public function getTypeName()
	{
		return $this->typeName;
	}

	function getTypeFlags()
	{
		return 0;
	}

	public function acceptDefaultValue($withDataType = 0)
	{
		return true;
	}

	public function getTypeMaxLength()
	{
		return INF;
	}

	public function has($id)
	{
		return ($id == K::TYPE_NAME);
	}

	public function get($id)
	{
		if ($id == self::TYPE_NAME)
			return $this->typeName;

		throw new ItemNotFoundException('Type property', $id);
	}

	private $typeName;
}