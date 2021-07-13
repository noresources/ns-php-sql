<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Filesystem;

interface StructureFilenameFactoryProviderInterface
{

	/**
	 *
	 * @return StructureFilenameFactoryInterface|NULL Structure filename factory or NULL
	 */
	function getStructureFilenameFactory();
}
