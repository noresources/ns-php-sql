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
 * Table set structure definition
 */
class NamespaceStructure extends StructureElement
{

	public function __construct(/*DatasourceStructure */$a_datasourceStructure, $name)
	{
		parent::__construct($name, $a_datasourceStructure);
	}

	/**
	 *
	 * @param TableStructure $a_table
	 */
	public final function addTableStructure(TableStructure $a_table)
	{
		$this->appendChild($a_table);
	}
}
