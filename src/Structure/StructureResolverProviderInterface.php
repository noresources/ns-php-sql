<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

/**
 * For classes which holds a StructureResolver
 */
interface StructureResolverProviderInterface
{

	/**
	 *
	 * @return StructureResolver
	 */
	function getStructureResolver();
}