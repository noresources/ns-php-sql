<?php
namespace NoreSources\SQL\Reference;

use NoreSources as ns;
use NoreSources\SQL as sql;
use NoreSources\SQL\Statement;
use NoreSources\SQL\Constants as K;

/**
 */
class StatementBuilder extends Statement\Builder
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

	public function getParameter($name, $position)
	{
		return ('$' . preg_replace('/[^a-zA-Z0-9_]/', '_', $name));
	}

	function getColumnTypeName(sql\TableColumnStructure $column)
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