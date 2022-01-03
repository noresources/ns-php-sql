<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Filesystem;

use NoreSources\Expression\Identifier;
use NoreSources\SQL\Structure\StructureElementInterface;

interface StructureFilenameFactoryInterface
{

	/**
	 *
	 * @param string|Identifier|StructureElementInterface $identifier
	 *        	Structure element instance or identifier
	 * @param stringn $type
	 *        	Structure element class name. This parameter can be omitted when a
	 *        	StructureElementInterface is passed as first argument
	 * @return string A file system path
	 */
	function buildStructureFilename($identifier, $type = null);
}
