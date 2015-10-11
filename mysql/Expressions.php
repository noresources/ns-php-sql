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

require_once (__DIR__ . "/../Expressions.php");
const MYSQL_TYPE_BINARY = "BINARY";
const MYSQL_TYPE_CHAR = "CHAR";
const MYSQL_TYPE_DATE = "DATE";
const MYSQL_TYPE_DATETIME = "DATETIME";
const MYSQL_TYPE_TIME = "TIME";
const MYSQL_TYPE_DECIMAL = "DECIMAL";
const MYSQL_TYPE_SIGNED = "SIGNED";
const MYSQL_TYPE_UNSIGNED = "UNSIGNED";

class MySQLStringData extends StringData
{

	public function __construct(Datasource $datasource, TableFieldStructure $structure)
	{
		parent::__construct($datasource, $structure);
	}

	protected function getDatasourceStringExpression()
	{
		return protect($this->datasource->apiCall("real_escape_string", $this->value, $this->datasource->resource()));
	}
}

class MySQLBinaryData extends BinaryData
{

	public function __construct(Datasource $datasource, TableFieldStructure $structure)
	{
		parent::__construct($datasource, $structure);
	}

	protected function getDatasourceBinaryExpression()
	{
		return protect($this->datasource->apiCall("real_escape_string", $this->value, $this->datasource->resource()));
	}
}


/**
 *
 */
class MySQLCastType implements ns\IExpression
{

	/**
	 *
	 * @param $a_type Type        	
	 */
	public function __construct($a_type = MYSQL_TYPE_UNSIGNED)
	{
		$this->m_type = $a_type;
	}

	public function expressionString($a_options = null)
	{
		return $this->m_type;
	}

	/**
	 * MySQL cast type name
	 * 
	 * @var string
	 */
	private $m_type;
}

class MySQLCast extends SQLFunction
{

	public function __construct(ns\IExpression $a_column, $a_type)
	{
		parent::__construct("CAST");
		if (!($a_type instanceof MySQLCastType))
		{
			$a_type = new MySQLCastType($a_type);
		}
		$e = new SQLAs($a_column, $a_type);

		$this->addParameter($e);
	}
}

?>
