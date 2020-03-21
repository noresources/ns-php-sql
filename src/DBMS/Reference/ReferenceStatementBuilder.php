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
use NoreSources\SQL\DBMS\ArrayObjectType;
use NoreSources\SQL\Statement\ParameterData;
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

	public static function serializeStringFallback($value)
	{
		return "'" . str_replace("'", "''", $value) . "'";
	}

	public function serializeString($value)
	{
		return self::serializeStringFallback($value);
	}

	public static function escapeIdentifierFallback($identifier, $before, $after)
	{
		$identifier = \str_replace($before, $before . $before, $identifier);
		if ($before != $after)
			$identifier = \str_replace($after, $after . $after, $identifier);
		return $before . $identifier . $after;
	}

	public function escapeIdentifier($identifier)
	{
		return self::escapeIdentifierFallback($identifier, '[', ']');
	}

	public function getParameter($name, ParameterData $parameters = null)
	{
		return ('$' . preg_replace('/[^a-zA-Z0-9_]/', '_', $name));
	}

	public function getColumnType(ColumnStructure $column)
	{
		$dataType = K::DATATYPE_UNDEFINED;
		if ($column->hasColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE))
			$dataType = $column->getColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE);

		$typeName = 'TEXT';

		switch ($dataType)
		{
			case K::DATATYPE_TIMESTAMP:
				$typeName = 'TIMESTAMP';
			break;
			case K::DATATYPE_DATE:
				$typeName = 'DATE';
			break;
			case K::DATATYPE_TIME:
			case K::DATATYPE_TIMEZONE:
				$typeName = 'TIME';
			break;
			case K::DATATYPE_DATETIME:
				$typeName = 'DATETIME';
			break;
			case K::DATATYPE_BINARY:
				$typeName = 'BLOB';
			break;
			case K::DATATYPE_BOOLEAN:
				$typeName = 'BOOL';
			break;
			case K::DATATYPE_INTEGER:
				$typeName = 'INTEGER';
			break;
			case K::DATATYPE_NUMBER:
			case K::DATATYPE_FLOAT:
				$typeName = 'REAL';
			break;
		}

		$props = [
			K::TYPE_PROPERTY_NAME => $typeName
		];

		$typeFlags = 0;
		if ($column->hasColumnProperty(K::COLUMN_PROPERTY_LENGTH))
			$typeFlags |= K::TYPE_FLAG_LENGTH;
		if ($column->hasColumnProperty(K::COLUMN_PROPERTY_FRACTION_SCALE))
			$typeFlags |= K::TYPE_FLAG_FRACTION_SCALE;

		foreach ([
			K::COLUMN_PROPERTY_MEDIA_TYPE
		] as $key)
		{
			if ($column->hasColumnProperty($key))
				$props[$key] = $column->getColumnProperty($key);
		}

		$props[K::TYPE_PROPERTY_FLAGS] = $typeFlags;

		return new ArrayObjectType($props);
	}
}