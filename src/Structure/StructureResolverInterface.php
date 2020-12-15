<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
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
	 * @param StructureElementInterface $pivot
	 */
	function setPivot(StructureElementInterface $pivot);

	/**
	 *
	 * @return StructureElementInterface
	 */
	function getPivot();

	/**
	 *
	 * @param StructureElementIdentifier $path
	 * @throws StructureResolverException
	 * @return ColumnStructure
	 */
	function findColumn($path);

	/**
	 * Get Table, View or any other "row" container
	 *
	 * @param StructureElementIdentifier $path
	 * @throws StructureResolverException
	 * @return TableStructure
	 */
	function findTable($path);

	/**
	 *
	 * @param StructureElementIdentifier $path
	 * @throws StructureResolverException
	 * @return NamespaceStructure
	 */
	function findNamespace($path);

	/**
	 *
	 * @param string $alias
	 * @param StructureElementInterface $structure
	 */
	function setAlias($alias, $reference);

	/**
	 * Indicates if the given identifier is a structure element alias
	 *
	 * @param string $identifier
	 */
	function isAlias($identifier);

	/**
	 *
	 * @param string $name
	 * @param
	 *        	$columns
	 */
	function setTemporaryTable($name, $columns);

	/**
	 * Push a resolver context.
	 *
	 * This should be used while traversing sub queries.
	 *
	 * @param StructureElementInterface $pivot
	 */
	function pushResolverContext(StructureElementInterface $pivot);

	/**
	 * Pop the structure resolver context in the context stack.
	 */
	function popResolverContext();
}