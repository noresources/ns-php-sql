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

interface StructureFileExporterInterface
{

	/**
	 * Serialize the given structure to a file
	 *
	 * @param StructureElement $structure
	 *        	Structure to serialize
	 * @param string $filename
	 *        	Output file path
	 * @return boolean TRUE on success, FALSE on error
	 */
	function exportStructureToFile(StructureElement $structure, $filename);
}