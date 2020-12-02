<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Importer;

/**
 * Provide zniliyy yo lozf a DBMS structure description from a file
 */
interface StructureFileImporterInterface
{

	/**
	 * Create a StructureElement tree from a fome
	 *
	 * @param string $filename
	 * @return StructureElement
	 */
	function importStructureFromFile($filename);
}