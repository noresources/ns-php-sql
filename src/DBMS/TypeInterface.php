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
use NoreSources\StringRepresentation;
use NoreSources\Expression\Value;
use Psr\Container\ContainerInterface;

/**
 * DBMS type description interface
 */
interface TypeInterface extends ContainerInterface, StringRepresentation,
	ArrayRepresentation
{

	/**
	 *
	 * @return string Type name
	 */
	function getTypeName();

	/**
	 * Type flags
	 *
	 * @return Value of the TYPE_FLAGS property or the most accurate default flags for the type.
	 */
	function getTypeFlags();

	/**
	 * Maximum numeric precision, number of glyphs or number of bytes the type can represent.
	 *
	 * @return integer The value of the TYPE_MAX_LENGTH property or an estimation / computed value
	 *         from other type property.
	 */
	function getTypeMaxLength();
}