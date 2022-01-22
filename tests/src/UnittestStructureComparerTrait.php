<?php

/**
 * Copyright © 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\Test;

use NoreSources\Container\Container;
use NoreSources\SQL\Structure\Comparer\StructureComparison;

trait UnittestStructureComparerTrait
{

	/**
	 *
	 * @param StructureComparison[] $comparisons
	 */
	protected function stringifyStructureComparison($comparisons,
		$withExtras = false)
	{
		if ($withExtras)
		{
			$a = [];
			foreach ($comparisons as $comparison)
			{

				/** @var StructureComparison $comparison */
				$c = \strval($comparison);
				$extras = $comparison->getExtras();
				if (\count($extras))
					$c .= PHP_EOL .
						Container::implodeValues($extras,
							[
								Container::IMPLODE_BEFORE => "\t",
								Container::IMPLODE_BETWEEN => PHP_EOL
							]);
				$a[] = $c;
			}
			\sort($a);
			$comparisons = $a;
		}

		$comparisons = \array_map('\strval', $comparisons);
		\sort($comparisons);
		return \trim(\implode(PHP_EOL, $comparisons)) . PHP_EOL;
	}
}
