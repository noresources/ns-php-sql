<?php

/**
 * Copyright © 2012-2016 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;

/*
 * @see http://www.postgresql.org/docs/9.1/interactive/datatype.html
 */
class PostgreSQLStringData extends StringData
{

	public function __construct(Datasource $datasource, TableFieldStructure $structure = null)
	{
		parent::__construct($datasource, $structure);
	}

	protected function getDatasourceStringExpression($value)
	{
		return (pg_escape_string($this->datasource->resource(), $value));
	}
}

class PostgreSQLBinaryData extends BinaryData
{

	public function __construct(Datasource $datasource, TableFieldStructure $structure = null)
	{
		parent::__construct($datasource, $structure);
	}

	protected function getDatasourceBinaryExpression($value)
	{
		return pg_escape_bytea($this->m_datasource->resource(), $value);
	}
	
	/*
	 * public function export($a_value)
	 * {
	 * return pg_unescape_bytea($this->m_datasource->resource(), $a_value);
	 * }
	 */
}
