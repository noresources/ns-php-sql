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
require_once (__DIR__ . '/../Data.php');

class MySQLStringData extends StringData
{

	public function __construct(Datasource $datasource, TableFieldStructure $structure = null)
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

	public function __construct(Datasource $datasource, TableFieldStructure $structure = null)
	{
		parent::__construct($datasource, $structure);
	}

	protected function getDatasourceBinaryExpression()
	{
		return protect($this->datasource->apiCall("real_escape_string", $this->value, $this->datasource->resource()));
	}
}
