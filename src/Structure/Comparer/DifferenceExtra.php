<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Comparer;

use NoreSources\Container\ArrayAccessContainerInterfaceTrait;
use Psr\Container\ContainerInterface;

class DifferenceExtra extends \ArrayObject implements
	ContainerInterface
{
	use ArrayAccessContainerInterfaceTrait;

	/**
	 * Difference type.
	 * Value could be one of KEY_* constant or any COLUMN_* (column property) constant
	 *
	 * @var string
	 */
	const KEY_TYPE = 'type';

	const KEY_PREVIOUS = 'previous';

	const KEY_NEW = 'new';

	const TYPE_TABLE = 'table';

	const TYPE_COLUMN = 'column';

	const TYPE_FLAGS = 'flags';

	const TYPE_EXPRESSION = 'expression';

	const TYPE_FOREIGN_TABLE = 'ftable';

	const TYPE_FOREIGN_COLUMN = 'fcolumn';
}
