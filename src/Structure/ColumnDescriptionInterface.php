<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\DataTypeProviderInterface;
use NoreSources\SQL\NameProviderInterface;
use NoreSources\Type\ArrayRepresentation;
use Psr\Container\ContainerInterface;

/**
 * Describe table or result column properties
 */
interface ColumnDescriptionInterface extends DataTypeProviderInterface,
	NameProviderInterface, ContainerInterface, \IteratorAggregate,
	ArrayRepresentation

{

	/**
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	function setColumnProperty($key, $value);
}
