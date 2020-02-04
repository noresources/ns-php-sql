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

use Psr\Container\ContainerInterface;

interface TypeInterface extends ContainerInterface
{

	/**
	 *
	 * @return string Type name
	 */
	function getTypeName();
}