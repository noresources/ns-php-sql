<?php

/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources as ns;

require_once (NS_PHP_PATH . '/core/arrays.php');

/**
 * Implementation of the SQL DROP query
 */
class DropTableQuery extends TableQuery
{

	public function __construct(Table $a_table)
	{
		parent::__construct($a_table);
	}

	public function expressionString($a_options = null)
	{
		return 'DROP TABLE ' . $this->table->expressionString($a_options);
	}

	public function execute($flags = 0)
	{
		$q = $this->expressionString();
		$result = $this->m_datasource->executeQuery($q);
		return ($result) ? true : false;
	}
}

class RenameTableQuery extends TableQuery
{

	public function __construct(Table $a_table, $a_newName)
	{
		parent::__construct($a_table);
		$this->m_newTable = new Table($a_table->owner(), $a_newName);
	}

	public function expressionString($a_options = null)
	{
		return 'ALTER TABLE ' . $this->table->expressionString($a_options) . ' RENAME TO ' . $this->m_newTable->expressionString($a_options);
	}

	public function execute($flags = 0)
	{
		$q = $this->expressionString();
		$result = $this->m_datasource->executeQuery($q);
		return ($result) ? true : false;
	}

	protected $m_newTable;
}

class CreateTableQuery extends IQuery
{
	/**
	 * @param SQLObject $a_parent
	 * @param TableStructure $a_structure
	 */
	public function __construct(SQLObject $a_parent, TableStructure $a_structure)
	{
		parent::__construct($a_parent->datasource);
		$this->m_structure;
	}
	
	public function execute($flags = 0)
	{
		
	}
	
	private $m_structure;
	
}

?>