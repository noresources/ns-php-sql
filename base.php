<?php

/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\Reporter;
use NoreSources as ns;

/**
 * Protect a string with the standard protection character (simple quote)
 *
 * @param string $a_strString
 * @param string $start
 * @param string $end
 * @return string
 *
 */
function protectString($a_strString, $start = '\'', $end = '\'')
{
	return $start . $a_strString . $end;
}

/**
 *
 * @param mixed $a_value
 *        	Value to import
 * @param Datasource $a_source
 *        	Datasource
 * @return Data or null
 */
function bestEffortImport($a_value, Datasource $a_source = null)
{
	if ($a_source)
	{
		$t = $a_source->guessDataType($$a_value);
		$v = $a_source->createData($t);
		$v->import($a_value);
		return $v;
	}

	$t = dataTypeFromValue($a_value);
	$v = null;
	if (!is_null($t))
	{
		/**
		 *
		 * @todo
		 */
		Reporter::fatalError(null, __METHOD__ . '() without datasource not supported yet');
	}

	return $v;
}

/**
 * Convert a variable in a TableColumn if possible
 *
 * @param
 *        	$a_mixedValue
 * @param
 *        	$a_provider
 * @return TableColumn
 *
 */
function mixedToTableColumn($a_mixedValue, ITableColumnProvider $a_provider)
{
	if ($a_mixedValue instanceof TableColumn)
	{
		return $a_mixedValue;
	}

	if (!is_string($a_mixedValue))
	{
		$a_mixedValue = null;
		ns\Reporter::error(null,
			__METHOD__ . '(): Invalid argument 1. string expected, got "' . gettype($a_mixedValue) .
			'"', __FILE__, __LINE__);
		return $a_mixedValue;
	}

	$name = $a_mixedValue;
	if (!($a_provider instanceof ITableColumnProvider) ||
		!($a_mixedValue = $a_provider->getColumn($a_mixedValue)))
	{
		$a_mixedValue = null;
		ns\Reporter::error(null,
			__METHOD__ . '(' . $name . ',' . get_class($a_provider) . '): Unable to retrieve field',
			__FILE__, __LINE__);
	}

	return $a_mixedValue;
}

/**
 * A generic method for ITableProvider::getTable
 *
 * @param ITableProvider $a_provider
 * @param string $a_name
 * @param string $a_aliasName
 * @param string $a_className
 * @return Table
 */
function tableProviderGenericTableObjectMethod(ITableProvider $a_provider, $a_structure, $a_name,
	$a_aliasName = null, $a_className = null, $useAliasAsName = false)
{
	$n = $a_name;
	$a = $a_aliasName;
	if ($useAliasAsName && $a_aliasName)
	{
		$n = $a_aliasName;
		$a = null;
	}

	$ds = $a_provider->datasource;
	$className = $ds->getDatasourceString(Datasource::kStringClassNameTable);

	$className = strlen($a_className) ? $a_className : $className;
	$result = new $className($a_provider, $n, $a, $a_structure);
	return $result;
}

function getStructure($object)
{
	$structure = null;
	if ($object instanceof TableColumn)
	{
		$structure = $object->getStructure();
	}
	elseif ($object instanceof StructureElement)
	{
		$structure = $object;
	}

	return $structure;
}

/**
 * Guess data type
 *
 * @param mixed $a_value
 * @return enum-like int from kDataType*
 */
function dataTypeFromValue($a_value)
{
	if (is_object($a_value) && ($a_value instanceof \DateTime))
	{
		return kDataTypeTimestamp;
	}
	if (is_null($a_value))
	{
		return kDataTypeNull;
	}
	elseif (is_bool($a_value))
	{
		$a_value = ($a_value) ? 1 : 0;
		return kDataTypeBoolean;
	}
	elseif (is_string($a_value))
	{
		return kDataTypeString;
	}
	elseif (is_numeric($a_value))
	{
		return kDataTypeNumber;
	}

	return kDataTypeBinary;
}

/**
 *
 * @param
 *        	$definition
 * @param
 *        	$typeUpperCase
 * @return array
 */
function parseDataTypeDefinition($definition, $typeUpperCase = false)
{
	$type = $definition;
	$size = false;
	$dsize = false;
	$regs = array();
	if (preg_match('/(([A-Za-z0-9_]+)(\(([0-9]+)(,([0-9]+)){0,1}\){0,1}))/', $type, $regs))
	{
		$type = ($typeUpperCase) ? strtoupper($regs[2]) : $regs[2];
		$size = $regs[4];
		$dsize = $regs[6];
	}

	return array(
		'type' => $type,
		'size' => $size,
		'dec_size' => $dsize,
		kStructureAcceptMultipleValues => false
	);
}

function glueElementDeclarations($k, $element)
{
	if (!($element instanceof ns\IExpression))
	{
		ns\Reporter::error($element, 'glueElementDeclaration(): Invalid ns\IExpression');
	}

	return $element->expressionString(kExpressionElementDeclaration);
}

function glueElementAliases($k, $element)
{
	if (!($element instanceof ns\IExpression))
	{
		ns\Reporter::error($element, 'glueElementDeclaration(): Invalid ns\IExpression');
	}

	return $element->expressionString(kExpressionElementAlias);
}

/**
 *
 * @deprecated Use SQL::begin ()
 * @param Datasource $a_datasource
 * @return boolean
 */
function begin(Datasource $a_datasource)
{
	return SQL::begin($a_datasource);
}

/**
 *
 * @deprecated Use SQL::commit ()
 * @param Datasource $a_datasource
 * @return boolean
 */
function commit(Datasource $a_datasource)
{
	return SQL::commit($a_datasource);
}

/**
 *
 * @deprecated Use SQL::rollback()
 * @param Datasource $a_datasource
 * @return boolean
 */
function rollback(Datasource $a_datasource)
{
	return SQL::rollback($a_datasource);
}

/**
 *
 * @param int|string $value
 *        	UNIX timestamp or string timestamp
 * @param \DateTimeZone $timezone
 *        	Time zone
 * @param Datasource $datasource
 *        	Datasourcce
 * @param TableColumnStructure $structure
 *        	Column property
 *        	
 * @return \DateTime|null
 */
function timestampToDateTime($value, \DateTimeZone $timezone = null, Datasource $datasource = null,
	TableColumnStructure $structure = null)
{
	if (!($timezone instanceof \DateTimeZone))
	{
		$timezone = new \DateTimeZone(date_default_timezone_get());
	}

	if ($value instanceof \DateTime)
		return $value;
	
	if (is_numeric($value))
	{
		return new \DateTime('@' . $value, $timezone);
	}
	
	if ($datasource)
	{
		$format = $datasource->getDatasourceString(Datasource::kStringTimestampFormat);
		$d = \DateTime::createFromFormat($format, $value, $timezone);
		if ($d instanceof \DateTime)
			return $d;
		else return (new \DateTime($value, $timezone));
	}
	
	$now = new \DateTime('now', $timezone);
	$time = strtotime($value, $now->getTimestamp());
	
	if (($time !== false) && ($time != -1)) // PHP <= 5.1
	{
		return new \DateTime('@' . $time, $timezone);
	}
	
	return null;
}

/**
 * An object that implements this interface can return
 * a clone of itself that differs only by its alias name
 */
interface IAliasedClone
{

	/**
	 * Return a clone of the object with another alias
	 *
	 * @param object $a_aliasNameName
	 */
	function cloneWithOtherAlias($a_aliasNameName);
}
