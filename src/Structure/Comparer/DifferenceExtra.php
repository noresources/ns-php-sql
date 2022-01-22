<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Comparer;

use NoreSources\Container\ArrayAccessContainerInterfaceTrait;
use NoreSources\Container\Container;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\Type\StringRepresentation;
use Psr\Container\ContainerInterface;

/**
 * Extra informations about structure difference
 */
class DifferenceExtra extends \ArrayObject implements
	ContainerInterface, StringRepresentation
{
	use ArrayAccessContainerInterfaceTrait;

	/**
	 * Difference data key.
	 *
	 * Difference type.
	 * Value could be one of KEY_* constant or any COLUMN_* (column property) constant
	 *
	 * @var string
	 */
	const KEY_TYPE = 'type';

	/**
	 * Difference data key.
	 *
	 * Reference structure element.
	 *
	 * @var string
	 */
	const KEY_PREVIOUS = 'previous';

	/**
	 * Difference data key.
	 *
	 * Target structure element.
	 *
	 * @var string
	 */
	const KEY_NEW = 'new';

	/**
	 * Difference type value.
	 *
	 * @var string
	 */
	const TYPE_TABLE = 'table';

	/**
	 * Difference type value.
	 *
	 * @var string
	 */
	const TYPE_COLUMN = 'column';

	/**
	 * Difference type value.
	 *
	 * @var string
	 */
	const TYPE_FLAGS = 'flags';

	/**
	 * Difference type value.
	 *
	 * @var string
	 */
	const TYPE_EXPRESSION = 'expression';

	/**
	 * Difference type value.
	 *
	 * @var string
	 */
	const TYPE_FOREIGN_TABLE = 'ftable';

	/**
	 * Difference type value.
	 *
	 * @var string
	 */
	const TYPE_FOREIGN_COLUMN = 'fcolumn';

	public function __toString()
	{
		$type = Container::keyValue($this, self::KEY_TYPE, '?');
		$p = Container::keyValue($this, self::KEY_PREVIOUS);
		$n = Container::keyValue($this, self::KEY_NEW);
		if ($p)
			$p = ($p instanceof StructureElementInterface) ? Identifier::make(
				$p) : $p;

		if ($n)
			$n = ($n instanceof StructureElementInterface) ? Identifier::make(
				$n) : $n;

		$s = $type;
		if ($p)
		{
			if (\is_array($p))
				$p = \implode(',', $p);

			if ($n)
			{
				$s .= ' ' . \strval($p);
				if (\is_array($n))
					$n = \implode(',', $n);
				$s .= ' -> ' . \strval($n);
			}
			else
				$s .= ' -' . \strval($p);
		}
		elseif ($n)
		{
			if (\is_array($n))
				$n = \implode(',', $n);
			$s .= ' +' . \strval($n);
		}

		return $s;
	}
}
