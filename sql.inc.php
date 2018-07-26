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

use NoreSources as ns;

if (!defined('NS_PHP_SQL_PATH'))
{
	define('NS_PHP_SQL_PATH', realpath(__DIR__));
}
const SQL_VERSION_MAJOR = 0;
const SQL_VERSION_MINOR = 2;
const SQL_VERSION_PATCH = 0;

/**
 * Version string of NoreSources SQL module.
 * The version string can be used with the PHP function version_compare()
 *
 * @return SQL module version
 */
function version_string()
{
	return (SQL_VERSION_MAJOR . '.' . SQL_VERSION_MINOR . '.' . SQL_VERSION_PATCH);
}

function version_number()
{
	return (SQL_VERSION_MAJOR * 10000 + SQL_VERSION_MINOR * 100 + SQL_VERSION_PATCH);
}

/**
 *
 * @defgroup structure 'Structure properties'
 * @{
 */

/**
 * The type of Datasource column.
 * Value type: integer
 */
const kStructureDatatype = 'datasourcetype';
/**
 * The column is part of a primary key.
 * Value type: boolean
 */
const kStructurePrimaryKey = 'primary';
/**
 * The column value is auto-incremented (integer column type only).
 * Value type: boolean
 */
const kStructureAutoincrement = 'auto';
const kStructureForeignKey = 'foreign';

/**
 * The column is indexed.
 * Value type: boolean
 */
const kStructureIndexed = 'index';
/**
 * The column accepts null values.
 * Value type: boolean
 */
const kStructureAcceptNull = 'null';
/**
 * Data size.
 *
 * Value type: integer
 */
const kStructureDataSize = 'size';

/**
 * Number of decimals (numeric field types).
 * Value type: integer
 */
const kStructureDecimalCount = 'decimalsize';

/**
 * List of valid values.
 * Value type: array
 */
const kStructureEnumeration = 'valid_values';
/**
 * Default value.
 * Value type: mixed
 */
const kStructureDefaultValue = 'default_value';

/**
 * The column accepts tuple values.
 * Value type: boolean
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
 *
 * @}
 */
// group 'structure'

/**
 *
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
 *
 * @} // group 'elemdisplay'
 */

/**
 *
 * @defgroup jointypes 'Join types'
 * @{
 */

/**
 * Natural join
 * Does not require any link field
 */
const kJoinNatural = 'sql.join.natural';
/**
 * Cross join
 * Cartesian product
 */
const kJoinCross = 'sql.join.cross';
/**
 * (Full) Outer join
 * Merge two tables.
 */
const kJoinOuter = 'sql.join.outer';
/**
 * Inner join
 * Merge obly lines which linkfields match in then two tables
 */
const kJoinInner = 'sql.join.inner';
/**
 * Left (outer) join
 * Take all elements of left table
 * and merge those which match link fields at the right
 */
const kJoinLeft = 'sql.join.left';
/**
 * Right (outer) join
 * Take all elements of right table
 * and merge those which match link fields at the left
 */
const kJoinRight = 'sql.join.right';

/**
 *
 * @} // group jointypes
 */

/**
 *
 * @defgroup datatypes 'Data types'
 * @{
 */

/**
 * null element
 */
const kDataTypeNull = 0x01;

/**
 * Manage all kind of string based element
 */
const kDataTypeString = 0x02;

/**
 * Integer number
 * @var int
 */
const kDataTypeInteger = 0x04;

/**
 * Decimal number
 * @var int
 */
const kDataTypeDecimal = 0x08;

/**
 * Manage all kind of number element
 */
const kDataTypeNumber = 0x0c; // 0x04 + 0x08

/**
 * All kind of date (date, datetime, time)
 */
const kDataTypeTimestamp = 0x10;

/**
 * Boolean (true / false)
 */
const kDataTypeBoolean = 0x20;

/**
 * Binary data
 */
const kDataTypeBinary = 0x40;

/**
 *
 * @}
 */
// group 'datatype'

/**
 *
 * @defgroup params 'A series of standard parameters for data source connection''
 * @{
 */

/**
 * A Datasource child class name.
 * Used in Datasource::create
 * @var string
 */
const kConnectionParameterClassname = 'sql.source.classname';

/**
 * Datasource connection user
 * @var string
 */
const kConnectionParameterUsername = 'sql.source.user';

/**
 * Datasource conneciion user password
 * @var string
 */
const kConnectionParameterPassword = 'sql.source.password';
/**
 * Datasource host (for network-based datasource)
 * @var string
 */
const kConnectionParameterHostname = 'sql.source.host';

/**
 * Datasource source file
 * @var string
 */
const kConnectionParameterFilename = 'sql.source.file';

/**
 * PostgresSQL: Postgres data source to connect to
 * SQLite: defines the name of the main SQLite main table
 * MySQL: If @c kConnectionParameterActiveTableSet is not set.
 * Set the active MySQL database
 * @var string
 */
const kConnectionParameterDatabasename = 'sql.source.database';

/**
 * Set the default/active table set of the ITableSetProvider
 */
const kConnectionParameterActiveTableSet = 'sql.source.tableset.default';

/**
 * Use persistent connection
 * @var bool
 */
const kConnectionParameterPersistent = 'sql.source.persistent';

/**
 * Read only connection
 * @var bool
 */
const kConnectionParameterReadOnly = 'sql.source.readonly';

/**
 * Set foreign key support
 *
 * For SQLite. Default behavior is to enable foreign keys
 * @var integer
 */
const kConnectionParameterForeignKeySupport = 'sql.source.foreignkey';

/**
 * Create datasource if possible (for file-based datasource)
 * @var bool
 */
const kConnectionParameterCreate = 'sql.source.create';

/**
 * A XML structure file
 * Used in Datasource::create()
 * @var string
 */
const kConnectionParameterStructureFile = 'sql.source.structure';

/**
 *
 * @}
 */
// group 'params'

/**
 *
 * @defgroup cnxflags 'Datasource connection flags'
 * @{
 */
const kConnectionPersistent = 0x01;

/**
 *
 * @}
 */
// group 'cnxflags'

/**
 *
 * @defgroup ExecutionQueryFlags
 * @{
 */
const kRecordsetFetchName = 0x01;
const kRecordsetFetchNumeric = 0x02;
const kRecordsetFetchBoth = 0x03;
/**
 *
 * @}
 */

/**
 *
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
 *
 * @}
 */
// group 'Datasourcequeries'

include_once (NS_PHP_SQL_PATH . '/sql.autoload.inc.php');
