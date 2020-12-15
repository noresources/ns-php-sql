<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\StructureElementIdentifier;
use NoreSources\SQL\Structure\StructureProviderInterface;

interface StructureExplorerInterface extends StructureProviderInterface
{

	/**
	 *
	 * @return string[]
	 */
	function getNamespaceNames();

	/**
	 *
	 * @param StructureElementIdentifier $parentIdentifier
	 */
	function getTableNames($parentIdentifier = null);

	/**
	 * Get table primary key constraint if any
	 *
	 * @param StructureElementIdentifier $tableIdentifier
	 *        	Table path
	 * @return PrimaryKeyTableConstraint|NULL
	 */
	function getTablePrimaryKeyConstraint($tableIdentifier);

	/**
	 *
	 * @param StructureElementIdentifier $tableIdentifier
	 *        	Table identifier
	 * @return array<int, ForeignKeyTableConstraint> List of foreign key constraints
	 */
	function getTableForeignKeyConstraints($tableIdentifier);

	/**
	 *
	 * @param StructureElementIdentifier $tableIdentifier
	 * @return string[]
	 */
	function getTableIndexNames($tableIdentifier);

	/**
	 *
	 * @param StructureElementIdentifier $tableIdentifier
	 * @return IndexTableConstraintInterface[] List of table indexes (excluding primary key)
	 */
	function getTableIndexes($tableIdentifier);

	/**
	 *
	 * @param StructureElementIdentifier $tableIdentifier
	 * @return array
	 */
	function getTableColumnNames($tableIdentifier);

	/**
	 *
	 * @param StructureElementIdentifier $tableIdentifier
	 *        	Table
	 * @param string $columnName
	 *        	Column name
	 * @return array
	 */
	function getTableColumn($tableIdentifier, $columnName);

	/**
	 *
	 * @param StructureElementIdentifier $parentIdentifier
	 */
	function getViewNames($parentIdentifier = null);
}
