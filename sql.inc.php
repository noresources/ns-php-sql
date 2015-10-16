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

if (!defined('NS_PHP_SQL_PATH'))
{
	define('NS_PHP_SQL_PATH', realpath(__DIR__));
}
const SQL_VERSION_MAJOR = 0;
const SQL_VERSION_MINOR = 1;
const SQL_VERSION_PATCH = 0;

/**
 * Version string of NoreSources XSLT module.
 * The version string can be used with the PHP function version_compare()
 *
 * @return XSLT module version
 */
function sql_version_string()
{
	return (SQL_VERSION_MAJOR . '.' . SQL_VERSION_MINOR . '.' . SQL_VERSION_PATCH);
}

function sql_version_number()
{
	return (SQL_VERSION_MAJOR * 10000 + SQL_VERSION_MINOR * 100 + SQL_VERSION_PATCH);
}

/**
 * @defgroup structure 'Structure properties'
 * @{
 */

/**
 * The name of the Datasource field type
 */
const kStructureDatatype = 'Datasourcetype';
/**
 * The field is part of a primary key
 * @var unknown
 */
const kStructurePrimaryKey = 'primary';
/**
 * The field value is auto-incremented (integer field type)
 */
const kStructureAutoincrement = 'auto';
/**
 * The field is indexed
 */
const kStructureIndexed = 'index';
/**
 * The field accepts null values
 */
const kStructureAcceptNull = 'null';
/**
 * Data size
 */
const kStructureDataSize = 'size';
/**
 * Number of decimals (numeric field types)
 */
const kStructureDecimalCount = 'decimalsize';
/**
 * List of valid values
 */
const kStructureEnumeration = 'valid_values';
/**
 * Default value
 */
const kStructureDefaultValue = 'default_value';

/**
 * The field accepts tuple values
 * @var unknown
 */
const kStructureAcceptMultipleValues = 'accept_multivalues';

// Datasource-relative / Application-relative properties


/**
 * The field typename (datasource dependant)
 */
const kStructureFieldTypename = 'typename';

/**
 * FieldValidator classname to use before insertion
 */
const kStructureValidatorClassname = 'validator_classname';

/**
 * @}
 */
// group 'structure'


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


include_once (NS_PHP_SQL_PATH . '/sql.autoload.inc.php');
