<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Exporter;

use NoreSources\SQL\Structure\StructureElementInterface;

/**
 * Provide serialization of StructureElement to a file
 */
interface StructureFileExporterInterface
{

	/**
	 * Serialize the given structure to a file
	 *
	 * @param StructureElementInterface $structure
	 *        	Structure to serialize
	 * @param string $filename
	 *        	Output file path
	 * @return boolean TRUE on success, FALSE on error
	 */
	function exportStructureToFile(StructureElementInterface $structure,
		$filename);
}