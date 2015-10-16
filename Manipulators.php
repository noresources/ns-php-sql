<?php

/**
 * Copyright © 2012-2015 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;
use NoreSources as ns;

require_once ('base.php');

abstract class TableManipulator
{

	public function __construct(Datasource $a_oSource, ITableProvider $a_oProvider = null)
	{
		if (!($a_oProvider instanceof ITableProvider))
		{
			if (($this instanceof ITableProvider))
			{
				$this->m_provider = $this;
				return;
			}
				
			ns\Reporter::fatalError($this, __METHOD__ . '(): Invalid call. Missing ITableProvider argument', __FILE__, __LINE__);
		}
		$this->m_provider = $a_oProvider;
		$this->m_datasource = $a_oSource;
	}

	/**
	 * Create a new table
	 * @param TableStructure $a_structure Table properties
	 */
	abstract public function create(TableStructure $a_structure);

	/**
	 * Rename a table
	 *
	 * @param string $a_strOldName
	 * @param string $a_newName
	 * @return boolean
	 */
	public function rename($a_strOldName, $a_newName)
	{
		if (!$this->m_provider->tableExists($a_strOldName, kObjectQueryDatasource))
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Table "' . to_string($a_strOldName) . '" does not exists', __FILE__, __LINE__);
		}

		if ($this->m_provider->tableExists($a_newName, kObjectQueryDatasource))
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Table "' . to_string($a_newName) . '" already exists', __FILE__, __LINE__);
		}

		$t = new Table($this->m_provider, $a_strOldName);
		$q = new RenameTableQuery($t, $a_newName);
		return ($q->execute()) ? true : false;
	}

	/**
	 * Remove a table
	 *
	 * @param string $a_name
	 * @return <code>true</code> if table exists and was successfully dropped
	 */
	public function delete($a_name)
	{
		if (!$this->m_provider->tableExists($a_name, kObjectQueryDatasource))
		{
			return ns\Reporter::error($this, 'Table "'.$a_name.'" does not exists');
		}
		
		$t = new Table($this->m_provider, $a_name);
		$q = new DropTableQuery($t);
		return $q->execute();
	}

	protected function postCreation(TableStructure $a_structure)
	{
		if ($this->m_provider->tableExists($a_structure->getName(), kObjectQueryDatasource))
		{
			return ns\Reporter::error($this, 'Table "' . $a_structure->getName() . '" already exists');
		}
		
		return true;
	}
	
	/**
	 * @var ITableProvider
	 */
	protected $m_provider;

	/**
	 *
	 * @var Datasource
	 */
	protected $m_datasource;
}

?>
