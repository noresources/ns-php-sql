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

	public function __construct(sql\ExpressionEvaluator $evaluator = null)
	{
		parent::__construct(K::BUILDER_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION | K::BUILDER_INSERT_DEFAULT_KEYWORD);

		if (!($evaluator instanceof sql\ExpressionEvaluator))
			$evaluator = new sql\ExpressionEvaluator();
		$this->setExpressionEvaluator($evaluator);
	}

	public function escapeString($value)
	{
		return \SQLite3::escapeString($value);
	}

	public function escapeIdentifier($identifier)
	{
		return '"' . $identifier . '"';
	}

	public function isValidParameterName($name)
	{
		return true;
	}

	public function normalizeParameterName($name, sql\StatementContext $context)
	{
		return $name;
	}

	public function getParameter($name, $index = -1)
	{
		return ':' . $name;
	}

	public function getColumnTymeName(TableColumnStructure $column)
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
}