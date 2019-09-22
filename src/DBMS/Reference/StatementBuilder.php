<?php

namespace NoreSources\SQL\Reference;

use NoreSources as ns;
use NoreSources\SQL as sql;
use NoreSources\SQL\Constants as K;

/**
 * 
 */
class StatementBuilder extends sql\StatementBuilder
{

	public function __construct($flags = 0)
	{
		parent::__construct($flags);
		$this->parameters = new \ArrayObject();
		$this->setExpressionEvaluator(new sql\ExpressionEvaluator());
	}

	public function escapeString($value)
	{
		return str_replace("'", "''", $value);
	}

	public function escapeIdentifier($identifier)
	{
		return '[' . $identifier . ']';
	}

	public function getParameter($name, $position)
	{
		return ('$' . preg_replace ('/[^a-zA-Z0-9_]/', '_', $name));
	}

	function getColumnTymeName(sql\TableColumnStructure $column)
	{
		$dataType = K::DATATYPE_UNDEFINED;
		if ($column->hasProperty(K::COLUMN_PROPERTY_DATA_TYPE))
			$dataType = $column->getProperty(K::COLUMN_PROPERTY_DATA_TYPE);
		
		switch ($dataType) {
			case K::DATATYPE_BINARY: return 'BLOB';
			case K::DATATYPE_BOOLEAN: return 'BOOL';
			case K::DATATYPE_INTEGER: return 'INTEGER';
			case K::DATATYPE_NUMBER:
			case K::DATATYPE_FLOAT: 
				return 'REAL';
			
		}
		
		return 'TEXT';
	}

}