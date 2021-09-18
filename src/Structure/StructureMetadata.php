<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\Container\ArrayAccessContainerInterfaceTrait;
use Psr\Container\ContainerInterface;

class StructureMetadata extends \ArrayObject implements
	ContainerInterface
{
	use ArrayAccessContainerInterfaceTrait;
}
