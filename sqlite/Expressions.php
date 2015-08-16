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

use \SQLite3;

require_once (__DIR__ . "/../Data.php");

class SQLiteStringData extends StringData
{

	public function __construct(Datasource $datasource, SQLTableFieldStructure $structure)
	{
		parent::__construct($datasource, $structure);
	}

	protected function getDatasourceStringExpression()
	{
		$value = $this->value;
		if (method_exists("\\SQLite3", "escapeString"))
		{
			$value = SQLite3::escapeString($a_value);
		}
		elseif (function_exists("sqlite_escape_string"))
		{
			$value = sqlite_escape_string($a_value);
		}
		
		return protect($value);
	}
}

class SQLiteBinaryData extends BinaryData
{

	public function __construct(Datasource $datasource, SQLTableFieldStructure $structure)
	{
		parent::__construct($datasource, $structure);
	}

	protected function getDatasourceBinaryExpression()
	{
		$value = $this->value;
		if (method_exists("\\SQLite3", "escapeString"))
		{
			$value = SQLite3::escapeString($a_value);
		}
		elseif (function_exists("sqlite_escape_string"))
		{
			$value = sqlite_escape_string($a_value);
		}
		
		return protect($value);
	}
}

?>
