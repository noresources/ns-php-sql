<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\SQL\Structure\StructureElementInterface;

/**
 * Common interface for all Structure modification queries
 */
interface StructureOperationQueryInterface
{

	/**
	 * Configure the query for this StructureElement
	 *
	 * Query instance MAY keep a reference to the given StructureElementInterface or
	 * gather information from it to be a valid query.
	 *
	 * @param StructureElementInterface $element
	 * @return $this
	 */
	function forStructure(StructureElementInterface $element);
}
