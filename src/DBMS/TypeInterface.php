<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\Expression\Value;
use NoreSources\SQL\DataTypeProviderInterface;
use NoreSources\Type\ArrayRepresentation;
use NoreSources\Type\StringRepresentation;
use Psr\Container\ContainerInterface;

/**
 * DBMS type description interface
 */
interface TypeInterface extends ContainerInterface, StringRepresentation,
	DataTypeProviderInterface, ArrayRepresentation
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
	 * Indicates if type accepts a default value of a given type.
	 *
	 * Default behavior should compare $withDataType with the TYPE_DEFAULT_DATA_TYPE property if
	 * any,
	 * otherwise compare $withDataType with (TYPE_DATA_TYPE property | DATATYPE_NULL) if any,
	 * otherwise return true if $withDataType is DATATYPE_NULL
	 *
	 * @param number $withDataType
	 *        	Default value data type
	 * @return boolean true if the type accepts a default value with the given data type.
	 */
	function acceptDefaultValue($withDataType = 0);

	/**
	 * Maximum numeric precision, number of glyphs or number of bytes the type can represent.
	 *
	 * @return integer The value of the TYPE_MAX_LENGTH property or an estimation / computed value
	 *         from other type property.
	 */
	function getTypeMaxLength();
}