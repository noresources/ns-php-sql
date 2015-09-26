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

/**
 * @defgroup elemdisplay 'Element expression display options'
 * @{
 */

/**
 * Display element using its full real name
 */
const kExpressionElementName = 0x1;

/**
 * Display element using its alias if available
 */
const kExpressionElementAlias = 0x2;

/**
 * Display element with its real name and declare its alias
 */
const kExpressionElementDeclaration = 0x3;

/**
 * @} // group 'elemdisplay'
 */

/**
 * @defgroup jointypes 'Join types'
 * @{
 */

/**
 * Natural join
 * Does not require any link field
 */
const kJoinNatural = 0x0;
/**
 * Cross join
 * Cartesian product
 */
const kJoinCross = 0x1;
/**
 * (Full) Outer join
 * Merge two tables.
 */
const kJoinOuter = 0x2;
/**
 * Inner join
 * Merge obly lines which linkfields match in then two tables
 */
const kJoinInner = 0x4;
/**
 * Left (outer) join
 * Take all elements of left table
 * and merge those which match link fields at the right
 */
const kJoinLeft = 0x8;
/**
 * Right (outer) join
 * Take all elements of right table
 * and merge those which match link fields at the left
 */
const kJoinRight = 0x10;

/**
 * @} // group jointypes
 */

/**
 * @defgroup datatypes 'Data types'
 * @{
 */

/**
 * null element
 */
const kDataTypeNull = 1;

/**
 * Manage all kind of string based element
 */
const kDataTypeString = 2;

/**
 * Manage all kind of number element
 */
const kDataTypeNumber = 4;

/**
 * All kind of date (date, datetime, time)
 */
const kDataTypeTimestamp = 8;

/**
 * Boolean (true / false)
 */
const kDataTypeBoolean = 16;

/**
 * Binary data
 */
const kDataTypeBinary = 32;

/**
 * @}
 */
// group 'datatype'


/**
 * @defgroup params 'A series of standard parameters for database connection''
 * @{
 */
const kConnectionParameterClassname = 'sql.source.classname';
const kConnectionParameterUsername = 'sql.source.user';
const kConnectionParameterPassword = 'sql.source.password';
const kConnectionParameterHostname = 'sql.source.host';
const kConnectionParameterFilename = 'sql.source.file';
const kConnectionParameterDatabasename = 'sql.source.database';
const kConnectionParameterPersistent = 'sql.source.persistent';
const kConnectionParameterReadOnly = 'sql.source.readonly';
const kConnectionParameterCreate = 'sql.source.create';

/**
 * @}
 */
// group 'params'

/**
 * @defgroup Datasourcequeries 'Datasource elements queries'
 * @{
 */
/**
 *
 * @var integer Query object in SQL structure
 */
const kObjectQuerySchema = 0x1;
/**
 *
 * @var integer Query object in 'physical' Datasource
 */
const kObjectQueryDatasource = 0x2;
/**
 *
 * @var integer Query object in both modes
 */
const kObjectQueryBoth = 0x3;

/**
 * @}
 */
// group 'Datasourcequeries'


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
 * @param
 *        	$a_value
 * @param
 *        	$a_source
 * @return SQLData
 *
 */
function bestEffortImport($a_value, Datasource $a_source = null)
{
	$t = guessDataType($a_value);
	if ($t !== null)
	{
		$v = $a_source->createData($t);
		$v->import($a_value);
	}
	else
	{
	/**
	 *
	 * @todo
	 *
	 *
	 *
	 *
	 */
	}
	return $v;
}

/**
 * Convert a variable in a TableField if possible
 *
 * @param
 *        	$a_mixedValue
 * @param
 *        	$a_provider
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
 * A generic method for ITableProvider::tableObject
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

/**
 * Guess data type
 *
 * @param mixed $a_value        	
 * @return enum-like int from DATATYPE_*
 */
function guessDataType($a_value)
{
	if ($a_value instanceof \DateTime)
	{
		return kDataTypeTimestamp;
	}
	if ($a_value === null)
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
 * @param SQLTableFieldStructure $a_structure        	
 * @return string
 */
function datatypeSizeString(ISQLDataType $a_datatype, SQLTableFieldStructure $a_structure = null)
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

?>