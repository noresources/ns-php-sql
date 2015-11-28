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
use NoreSources\Reporter;

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
 * @param mixed $a_value Value to import
 * @param Datasource $a_source Datasource
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
	
	$t = guessDataType($a_value);
	$v = null;
	if (!is_null($t))
	{
		/**
		 * @todo
		 */
		Reporter::fatalError(null, __METHOD__ . '() without datasource not supported yet');
	}
	
	return $v;
}

/**
 * Convert a variable in a TableField if possible
 *
 * @param $a_mixedValue
 * @param $a_provider
 * @return TableField
 *
 */
function mixedToTableField($a_mixedValue, ITableFieldProvider $a_provider)
{
	if ($a_mixedValue instanceof TableField)
	{
		return $a_mixedValue;
	}
	
	if (!is_string($a_mixedValue))
	{
		$a_mixedValue = null;
		ns\Reporter::addError(null, __METHOD__ . '(): Invalid argument 1. string expected, got "' . gettype($a_mixedValue) . '"', __FILE__, __LINE__);
		return $a_mixedValue;
	}
	
	$name = $a_mixedValue;
	if (!($a_provider instanceof ITableFieldProvider) || !($a_mixedValue = $a_provider->fieldObject($a_mixedValue)))
	{
		$a_mixedValue = null;
		ns\Reporter::addError(null, __METHOD__ . '(' . $name . ',' . get_class($a_provider) . '): Unable to retrieve field', __FILE__, __LINE__);
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
function tableProviderGenericTableObjectMethod(ITableProvider $a_provider, $a_structure, $a_name, $a_aliasName = null, $a_className = null, $useAliasAsName = false)
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
	
	$class = strlen($a_className) ? $a_className : $className;
	$result = new $class($a_provider, $n, $a, $a_structure);
	return $result;
}

function guessStructureElement ($object)
{
	$structure = null;
	if ($object instanceof TableField)
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
function guessDataType($a_value)
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
 * @param $definition
 * @param $typeUpperCase
 * @return array
 */
function parseDataTypeDefinition($definition, $typeUpperCase = false)
{
	$type = $definition;
	$size = false;
	$dsize = false;
	$regs = array ();
	if (preg_match('/(([A-Za-z0-9_]+)(\(([0-9]+)(,([0-9]+)){0,1}\){0,1}))/', $type, $regs))
	{
		$type = ($typeUpperCase) ? strtoupper($regs [2]) : $regs [2];
		$size = $regs [4];
		$dsize = $regs [6];
	}
	
	return array (
			'type' => $type,
			'size' => $size,
			'dec_size' => $dsize,
			kStructureAcceptMultipleValues => false 
	);
}

/**
 * Format field size declaration
 *
 * Assumes similar string for all Datasources
 *
 * @param ISQLDataType $a_datatype
 * @param TableFieldStructure $a_structure
 * @return string
 */
function datatypeSizeString(ISQLDataType $a_datatype, TableFieldStructure $a_structure = null)
{
	$str = '';
	
	if ($a_datatype instanceof ISQLSizeableDecimalDataType)
	{
		$str .= '(';
		$ts = $a_structure->getProperty(kStructureDataSize);
		$ds = $a_structure->getProperty(kStructureDecimalCount);
		$str .= $ts . ',' . $ds;
		$str .= ')';
	}
	if ($a_datatype instanceof ISQLSizeableDataType)
	{
		$str .= '(';
		$str .= ns\array_keyvalue($a_propertes, kStructureDataSize, $a_datatype->maxSize(), false);
		$str .= ')';
	}
	
	return $str;
}

function glueElementDeclarations($k, $element)
{
	if (!($element instanceof ns\IExpression))
	{
		ns\Reporter::addError($element, 'glueElementDeclaration(): Invalid ns\IExpression');
	}
	
	return $element->expressionString(kExpressionElementDeclaration);
}

function glueElementAliases($k, $element)
{
	if (!($element instanceof ns\IExpression))
	{
		ns\Reporter::addError($element, 'glueElementDeclaration(): Invalid ns\IExpression');
	}
	
	return $element->expressionString(kExpressionElementAlias);
}

function begin(Datasource $a_datasource)
{
	if ($a_datasource instanceof ITransactionBlock)
	{
		$a_datasource->startTransaction();
		return true;
	}
	return false;
}

function commit(Datasource $a_datasource)
{
	if ($a_datasource instanceof ITransactionBlock)
	{
		$a_datasource->commitTransaction();
		return true;
	}
	return false;
}

function rollback(Datasource $a_datasource)
{
	if ($a_datasource instanceof ITransactionBlock)
	{
		$a_datasource->rollbackTransaction();
		return true;
	}
	return false;
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
