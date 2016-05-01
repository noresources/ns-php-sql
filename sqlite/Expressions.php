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
use \SQLite3;

class SQLiteStringData extends StringData
{

	public function __construct(Datasource $datasource, TableFieldStructure $structure = null)
	{
		parent::__construct($datasource, $structure);
	}

	protected function getDatasourceStringExpression($a_value)
	{
		if (method_exists('\\SQLite3', 'escapeString'))
		{
			$value = SQLite3::escapeString($a_value);
		}
		elseif (function_exists('sqlite_escape_string'))
		{
			$value = sqlite_escape_string($a_value);
		}
		
		return $value;
	}
}

class SQLiteBinaryData extends BinaryData
{

	public function __construct(Datasource $datasource, TableFieldStructure $structure = null)
	{
		parent::__construct($datasource, $structure);
	}

	protected function getDatasourceBinaryExpression($a_value)
	{
		if (method_exists('\\SQLite3', 'escapeString'))
		{
			$value = SQLite3::escapeString($a_value);
		}
		elseif (function_exists('sqlite_escape_string'))
		{
			$value = sqlite_escape_string($a_value);
		}
		
		return $value;
	}
}
