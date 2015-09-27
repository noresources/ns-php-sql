<?php
/////////////////////////////////////////////////////////////////////////////////////////////////////
// NoreSources::php
// <tpl name="license" prepend="// "/>
// 
/////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * PostgreSQL data types
 * @package PostgreSQL
 */

if (!defined("NS_FILE_NS"))
{ die("ns.php must be included first"); }

if (NS_USE_NS_INCLUDE)
{
	ns::xinclude("sql/datatypes");
}
else
{
	require_once(NS_PHP_PATH."/sql/datatypes.5.php");
}

/*
 * @see http://www.postgresql.org/docs/9.1/interactive/datatype.html
 */

/**
 * Use mysql_real_escape_string()
 * @author renaud
 */
class PostgreSQLVarCharDataType extends SQLVarcharDataType
{
	public function __construct(PostgreSQLDatasource $a_datasource, $a_strName, $a_min, $a_max)
	{
		parent::__construct(get_class($a_datasource), $a_strName, $a_min, $a_max);
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
class PostgreSQLStringDataType extends SQLStringDataType
{
	public function __construct(PostgreSQLDatasource $a_datasource, $a_strName)
	{
		parent::__construct(get_class($a_datasource), $a_strName);
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
class PostgreSQLBinaryDataType extends SQLBinaryDataType
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
