<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

/**
 * For object holding a reference to a StructureElement
 */
interface StructureProviderInterface
{

	/**
	 *
	 * @return \NoreSources\SQL\Structure\StructureElement
	 */
	function getStructure();
}