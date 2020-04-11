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

/**
 * Resolve dot-separated structure path and alias to StructureElement
 */
interface StructureResolverInterface
{

	/**
	 * Define the reference node and reset cache
	 *
	 * @param StructureElement $pivot
	 */
	function setPivot(StructureElement $pivot);

	/**
	 *
	 * @return \NoreSources\SQL\StructureElement
	 */
	function getPivot();

	/**
	 *
	 * @param string $path
	 * @throws StructureResolverException
	 * @return \NoreSources\SQL\ColumnStructure
	 */
	function findColumn($path);

	/**
	 *
	 * @param string $path
	 * @throws StructureResolverException
	 * @return \NoreSources\SQL\TableStructure
	 */
	function findTable($path);

	/**
	 *
	 * @param string $path
	 * @throws StructureResolverException
	 * @return \NoreSources\SQL\NamespaceStructure
	 */
	function findNamespace($path);

	/**
	 *
	 * @param string $alias
	 * @param StructureElement $structure
	 */
	function setAlias($alias, StructureElement $reference);

	/**
	 * Indicates if the given identifier is a structure element alias
	 *
	 * @param string $identifier
	 */
	function isAlias($identifier);

	/**
	 * Push a resolver context.
	 *
	 * This should be used while traversing sub queries.
	 *
	 * @param StructureElement $pivot
	 */
	function pushResolverContext(StructureElement $pivot = null);

	/**
	 * Pop the structure resolver context in the context stack.
	 */
	function popResolverContext();
}