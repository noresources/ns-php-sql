<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
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
