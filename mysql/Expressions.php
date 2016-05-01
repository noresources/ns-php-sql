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

use NoreSources as ns;

class MySQLStringData extends StringData
{

	public function __construct(Datasource $datasource, TableFieldStructure $structure = null)
	{
		parent::__construct($datasource, $structure);
	}

	protected function getDatasourceStringExpression($value)
	{
		return ($this->datasource->apiCall("real_escape_string", $value, $this->datasource->resource()));
	}
}

class MySQLBinaryData extends BinaryData
{

	public function __construct(Datasource $datasource, TableFieldStructure $structure = null)
	{
		parent::__construct($datasource, $structure);
	}

	protected function getDatasourceBinaryExpression($value)
	{
		return ($this->datasource->apiCall("real_escape_string", $value, $this->datasource->resource()));
	}
}
