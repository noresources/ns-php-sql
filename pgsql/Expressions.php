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

require_once(__DIR__ . '/../Data.php');

/*
 * @see http://www.postgresql.org/docs/9.1/interactive/datatype.html
 */

/**
 * Use mysql_real_escape_string()
 */
class PostgreSQLVarCharData extends StringData
{
	public function __construct(PostgreSQLDatasource $a_datasource, $a_strName, $a_min, $a_max)
	{
		parent::__construct($a_datasource, $a_strName, $a_min, $a_max);
		$this->m_datasource = $a_datasource;
	}

	public function import($a_value, SQLTableFieldStructure $a_properties = null)
	{
		if (is_string($a_value))
		{
			$a_value = pg_escape_string($this->m_datasource->resource(), $a_value);
		}
		return parent::import($a_value, $a_properties);
	}

	protected $m_datasource;
}

/**
 * @author renaud
 */
class PostgreSQLStringData extends StringData
{
	public function __construct(PostgreSQLDatasource $a_datasource, $a_strName)
	{
		parent::__construct($a_datasource, $a_strName);
		$this->m_datasource = $a_datasource;
	}

	public function import($a_value, SQLTableFieldStructure $a_properties = null)
	{
		if (is_string($a_value))
		{
			$a_value = pg_escape_string($this->m_datasource->resource(), $a_value);
		}
		return parent::import($a_value, $a_properties);
	}

	protected $m_datasource;
}

/**
 * @author renaud
 */
class PostgreSQLBinaryData extends BinaryData
{
	public function __construct(PostgreSQLDatasource $a_datasource, $a_strName)
	{
		parent::__construct(get_class($a_datasource), $a_strName);
		$this->m_datasource = $a_datasource;
	}
	
	public function import($a_value, SQLTableFieldStructure $a_properties = null)
	{
		$a_value = pg_escape_bytea($this->m_datasource->resource(), $a_value);
		return parent::import($a_value, $a_properties);
	}
	
	public function export($a_value, SQLTableFieldStructure $a_properties = null)
	{
		return pg_unescape_bytea($this->m_datasource->resource(), $a_value);
	}
}


?>
