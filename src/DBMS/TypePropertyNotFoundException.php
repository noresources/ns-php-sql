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

use NoreSources\NotFoundException;

class TypePropertyNotFoundException extends NotFoundException
{

	/**
	 *
	 * @var TypeInterface
	 */
	public $type;

	/**
	 *
	 * @var string
	 */
	public $propertyKey;

	/**
	 *
	 * @param TypeInterface $type
	 * @param string $property
	 */
	public function __construct(TypeInterface $type, $property)
	{
		$this->type = $type;
		$this->propertyKey = $property;
	}
}