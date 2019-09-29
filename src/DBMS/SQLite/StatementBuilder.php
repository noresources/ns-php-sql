<?php

// NAmespace
namespace NoreSources\SQL\SQLite;

// Aliases
use NoreSources as ns;
use NoreSources\SQL as sql;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\TableColumnStructure;

class StatementBuilder extends sql\StatementBuilder
{

	public function __construct()
	{
		parent::__construct();

		$this->setBuilderFlags(K::BUILDER_DOMAIN_GENERIC,
			K::BUILDER_IF_EXISTS | K::BUILDER_IF_NOT_EXISTS);
		$this->setBuilderFlags(K::BUILDER_DOMAIN_SELECT,
			K::BUILDER_SELECT_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION);
		$this->setBuilderFlags(K::BUILDER_DOMAIN_INSERT, K::BUILDER_INSERT_DEFAULT_KEYWORD);
	}

	public function escapeString($value)
	{
		return \SQLite3::escapeString($value);
	}

	public function escapeIdentifier($identifier)
	{
		return '"' . $identifier . '"';
	}

	public function getParameter($name, $position)
	{
		return (':' . preg_replace('/[^a-zA-Z0-9_]/', '_', $name));
	}

	public function getColumnTypeName(TableColumnStructure $column)
	{
		$dataType = K::DATATYPE_UNDEFINED;
		if ($column->hasProperty(K::COLUMN_PROPERTY_DATA_TYPE))
			$dataType = $column->getProperty(K::COLUMN_PROPERTY_DATA_TYPE);

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