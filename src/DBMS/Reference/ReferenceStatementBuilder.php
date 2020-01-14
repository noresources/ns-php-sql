<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Reference;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Statement\ParameterMap;
use NoreSources\SQL\Statement\StatementBuilder;
use NoreSources\SQL\Structure\ColumnStructure;

/**
 */
class ReferenceStatementBuilder extends StatementBuilder
{

	public function __construct($domainFlags = [])
	{
		parent::__construct();

		if (!\is_array($domainFlags))
			$domainFlags = [
				K::BUILDER_DOMAIN_GENERIC => $domainFlags
			];

		foreach ($domainFlags as $domain => $flags)
			$this->setBuilderFlags($domain, $flags);

		$this->parameters = new \ArrayObject();
	}

	public function escapeString($value)
	{
		return str_replace("'", "''", $value);
	}

	public function escapeIdentifier($identifier)
	{
		return '[' . $identifier . ']';
	}

	public function getParameter($name, ParameterMap $parameters = null)
	{
		return ('$' . preg_replace('/[^a-zA-Z0-9_]/', '_', $name));
	}

	function getColumnTypeName(ColumnStructure $column)
	{
		$dataType = K::DATATYPE_UNDEFINED;
		if ($column->hasColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE))
			$dataType = $column->getColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE);

		switch ($dataType)
		{
			case K::DATATYPE_TIMESTAMP:
				return 'TIMESTAMP';
			case K::DATATYPE_DATE:
				return 'DATE';
			case K::DATATYPE_TIME:
			case K::DATATYPE_TIMEZONE:
				return 'TIME';
			case K::DATATYPE_DATETIME:
				return 'DATETIME';
			case K::DATATYPE_BINARY:
				return 'BLOB';
			case K::DATATYPE_BOOLEAN:
				return 'BOOL';
			case K::DATATYPE_INTEGER:
				return 'INTEGER';
			case K::DATATYPE_NUMBER:
			case K::DATATYPE_FLOAT:
				return 'REAL';
		}

		return 'TEXT';
	}
}