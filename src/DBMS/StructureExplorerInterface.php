<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
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
	 * @param Identifier $parentIdentifier
	 */
	function getTableNames($parentIdentifier = null);

	/**
	 * Get table primary key constraint if any
	 *
	 * @param Identifier $tableIdentifier
	 *        	Table path
	 * @return PrimaryKeyTableConstraint|NULL
	 */
	function getTablePrimaryKeyConstraint($tableIdentifier);

	/**
	 *
	 * @param Identifier $tableIdentifier
	 *        	Table identifier
	 * @return array<int, ForeignKeyTableConstraint> List of foreign key constraints
	 */
	function getTableForeignKeyConstraints($tableIdentifier);

	/**
	 *
	 * @param Identifier $tableIdentifier
	 *        	Table identifier
	 * @return array<int, UniqueTableConstraint> List of UNIQUE constraints
	 */
	function getTableUniqueConstraints($tableIdentifier);

	/**
	 *
	 * @param Identifier $tableIdentifier
	 * @return string[] Names of all table indexes which are not table column constraints
	 */
	function getTableIndexNames($tableIdentifier);

	/**
	 *
	 * @param Identifier $tableIdentifier
	 * @return IndexStructure[] List of table indexes (excluding table column constraints)
	 */
	function getTableIndexes($tableIdentifier);

	/**
	 *
	 * @param Identifier $tableIdentifier
	 * @return array
	 */
	function getTableColumnNames($tableIdentifier);

	/**
	 *
	 * @param Identifier $tableIdentifier
	 *        	Table
	 * @param string $columnName
	 *        	Column name
	 * @return array
	 */
	function getTableColumn($tableIdentifier, $columnName);

	/**
	 *
	 * @param Identifier $parentIdentifier
	 */
	function getViewNames($parentIdentifier = null);
}
