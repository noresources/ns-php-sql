<?php

/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use Psr\Container\ContainerInterface;

interface AssetMapInterface extends ContainerInterface,
	\IteratorAggregate, \Countable
{
}
