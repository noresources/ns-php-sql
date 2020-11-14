<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\DataTypeProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * Describe table or result column properties
 */
interface ColumnDescriptionInterface extends DataTypeProviderInterface,
	ContainerInterface, \IteratorAggregate
{

	/**
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	function setColumnProperty($key, $value);
}
