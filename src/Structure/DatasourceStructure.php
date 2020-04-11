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
 * Top-level structure container definition
 */
class DatasourceStructure extends StructureElement
{

	/**
	 *
	 * @param string $name
	 *        	Datasource class name
	 */
	public function __construct($name = 'datasource')
	{
		parent::__construct(((is_string($name) && strlen($name)) ? $name : 'datasource'));
	}
}

