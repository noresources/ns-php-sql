<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\SQLite;

// Aliases
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Statement\ParameterMap;
use NoreSources\SQL\Statement\StatementBuilder;
use NoreSources\SQL\Structure\ColumnStructure;

class SQLiteStatementBuilder extends StatementBuilder
{

	public function __construct()
	{
		parent::__construct();

		$this->setBuilderFlags(K::BUILDER_DOMAIN_GENERIC,
			K::BUILDER_IF_EXISTS | K::BUILDER_IF_NOT_EXISTS);
		$this->setBuilderFlags(K::BUILDER_DOMAIN_SELECT,
			K::BUILDER_SELECT_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION);
		$this->setBuilderFlags(K::BUILDER_DOMAIN_INSERT, K::BUILDER_INSERT_DEFAULT_VALUES);
	}

	public function escapeString($value)
	{
		return \SQLite3::escapeString($value);
	}

	public function escapeIdentifier($identifier)
	{
		return '"' . $identifier . '"';
	}

	public function getParameter($name, ParameterMap $parameters = null)
	{
		return (':' . preg_replace('/[^a-zA-Z0-9_]/', '_', $name));
	}

	public static function getSQLiteColumnTypeName(ColumnStructure $column)
	{
		$dataType = K::DATATYPE_UNDEFINED;
		if ($column->hasColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE))
			$dataType = $column->getColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE);

		switch ($dataType)
		{
			case K::DATATYPE_BINARY:
				return 'BLOB';
			case K::DATATYPE_NUMBER:
			case K::DATATYPE_FLOAT:
				return 'REAL';
			case K::DATATYPE_BOOLEAN:
			case K::DATATYPE_INTEGER:
				return 'INTEGER';
			case K::DATATYPE_NULL:
				return NULL;
		}

		return 'TEXT';
	}

	public function getColumnTypeName(ColumnStructure $column)
	{
		return self::getSQLiteColumnTypeName($column);
	}

	public function getKeyword($keyword)
	{
		switch ($keyword)
		{
			case K::KEYWORD_TRUE:
				return 1;
			case K::KEYWORD_FALSE:
				return 0;
		}

		return parent::getKeyword($keyword);
	}
}