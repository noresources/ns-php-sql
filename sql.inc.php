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

if (!defined('NS_PHP_SQL_PATH'))
{
	define('NS_PHP_SQL_PATH', realpath(__DIR__));
}

const SQL_VERSION_MAJOR = 0;

const SQL_VERSION_MINOR = 2;

const SQL_VERSION_PATCH = 0;

class SQL
{

	const DATATYPE_UNDEFINED = 0;

	const DATATYPE_NULL = 0x01;

	const DATATYPE_STRING = 0x02;

	const DATATYPE_INTEGER = 0x04;

	const DATATYPE_FLOAT = 0x08;

	const DATATYPE_NUMBER = 0x0c;

	const DATATYPE_TIMESTAMP = 0x10;

	const DATATYPE_BOOLEAN = 0x20;

	const DATATYPE_BINARY = 0x40;

	/**
	 * Natural join
	 * Does not require any link field
	 */
	const JOIN_NATURAL = 'sql.join.natural';

	/**
	 * Cross join
	 * Cartesian product
	 */
	const JOIN_CROSS = 'sql.join.cross';

	/**
	 * (Full) Outer join
	 * Merge two tables.
	 */
	const JOIN_OUTER = 'sql.join.outer';

	/**
	 * Inner join
	 * Merge obly lines which linkfields match in then two tables
	 */
	const JOIN_INNER = 'sql.join.inner';

	/**
	 * Left (outer) join
	 * Take all elements of left table
	 * and merge those which match link fields at the right
	 */
	const JOIN_LEFT = 'sql.join.left';

	/**
	 * Right (outer) join
	 * Take all elements of right table
	 * and merge those which match link fields at the left
	 */
	const JOIN_RIGHT = 'sql.join.right';

	public static function getDataTypeName($type)
	{
		switch ($type)
		{
			case self::DATATYPE_BINARY:
				return 'binary';
			case self::DATATYPE_BOOLEAN:
				return 'boolean';
			case self::DATATYPE_INTEGER:
				return 'integer';
			case self::DATATYPE_NULL:
				return 'null';
			case self::DATATYPE_FLOAT:
			case self::DATATYPE_NUMBER:
				return 'float';
			default:
				return 'string';
		}
	}

	public static function getBasePath()
	{
		return NS_PHP_SQL_PATH;
	}

	public static function versionString()
	{
		return (SQL_VERSION_MAJOR . '.' . SQL_VERSION_MINOR . '.' . SQL_VERSION_PATCH);
	}

	public static function versionNumber()
	{
		return (SQL_VERSION_MAJOR * 10000 + SQL_VERSION_MINOR * 100 + SQL_VERSION_PATCH);
	}

	/**
	 * Gegin transaction block
	 *
	 * @param Datasource $a_datasource
	 * @return boolean
	 */
	public static function begin(Datasource $a_datasource)
	{
		if ($a_datasource instanceof ITransactionBlock)
		{
			$a_datasource->startTransaction();
			return true;
		}
		return false;
	}

	/**
	 * Commit transaction block
	 *
	 * @param Datasource $a_datasource
	 * @return boolean
	 */
	public static function commit(Datasource $a_datasource)
	{
		if ($a_datasource instanceof ITransactionBlock)
		{
			$a_datasource->commitTransaction();
			return true;
		}
		return false;
	}

	/**
	 * Rollback transaction block
	 *
	 * @param Datasource $a_datasource
	 * @return boolean
	 */
	public static function rollback(Datasource $a_datasource)
	{
		if ($a_datasource instanceof ITransactionBlock)
		{
			$a_datasource->rollbackTransaction();
			return true;
		}
		return false;
	}
}

/**
 * Version string of NoreSources SQL module.
 * The version string can be used with the PHP function version_compare()
 *
 * @return SQL module version
 */
function version_string()
{
	return SQL::versionString();
}

function version_number()
{
	return SQL::versionNumber();
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
const kExpressionElementName = 0x01;

/**
 * Display element using its alias if available
 */
const kExpressionElementAlias = 0x02;

/**
 * Display element with its real name and declare its alias
 */
const kExpressionElementDeclaration = 0x03;

const kExpressionElementUnion = 0x04;

/**
 *
 * @} // group 'elemdisplay'
 */

/**
 *
 * @defgroup datatypes 'Data types'
 * @{
 */

/**
 * null element
 */
const kDataTypeNull = SQL::DATATYPE_NULL;

/**
 * Manage all kind of string based element
 */
const kDataTypeString = SQL::DATATYPE_STRING;

/**
 * Integer number
 */
const kDataTypeInteger = SQL::DATATYPE_INTEGER;

/**
 * Decimal numbers
 */
const kDataTypeDecimal = SQL::DATATYPE_FLOAT;

/**
 * All kind of number element
 */
const kDataTypeNumber = SQL::DATATYPE_NUMBER;

// 0x04 + 0x08

/**
 * All kind of date (date, datetime, time)
 */
const kDataTypeTimestamp = SQL::DATATYPE_TIMESTAMP;

/**
 * Boolean (true / false)
 */
const kDataTypeBoolean = SQL::DATATYPE_BOOLEAN;

/**
 * Binary data
 */
const kDataTypeBinary = SQL::DATATYPE_BINARY;

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
 *
 * @var string
 */
const kConnectionParameterClassname = 'sql.source.classname';

/**
 * Datasource connection user
 *
 * @var string
 */
const kConnectionParameterUsername = 'sql.source.user';

/**
 * Datasource conneciion user password
 *
 * @var string
 */
const kConnectionParameterPassword = 'sql.source.password';

/**
 * Datasource host (for network-based datasources)
 *
 * @var string
 */
const kConnectionParameterHostname = 'sql.source.host';

/**
 * Datasource port (for network-based datasources)
 *
 * @var string
 */
const kConnectionParameterPort = 'sql.source.port';

/**
 * Datasource source file
 *
 * @var string
 */
const kConnectionParameterFilename = 'sql.source.file';

/**
 * PostgresSQL: Postgres data source to connect to
 * SQLite: defines the name of the main SQLite main table
 * MySQL: If @c kConnectionParameterActiveTableSet is not set.
 * Set the active MySQL database
 *
 * @var string
 */
const kConnectionParameterDatabasename = 'sql.source.database';

/**
 * Set the default/active table set of the ITableSetProvider
 */
const kConnectionParameterActiveTableSet = 'sql.source.tableset.default';

/**
 * Use persistent connection
 *
 * @var bool
 */
const kConnectionParameterPersistent = 'sql.source.persistent';

/**
 * Read only connection
 *
 * @var bool
 */
const kConnectionParameterReadOnly = 'sql.source.readonly';

/**
 * Set foreign key support
 *
 * For SQLite. Default behavior is to enable foreign keys
 *
 * @var integer
 */
const kConnectionParameterForeignKeySupport = 'sql.source.foreignkey';

/**
 * Create datasource if possible (for file-based datasource)
 *
 * @var bool
 */
const kConnectionParameterCreate = 'sql.source.create';

/**
 * A XML structure file
 * Used in Datasource::create()
 *
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

const kJoinNatural = SQL::JOIN_NATURAL;

const kJoinCross = SQL::JOIN_CROSS;

const kJoinOuter = SQL::JOIN_OUTER;

const kJoinInner = SQL::JOIN_INNER;

const kJoinLeft = SQL::JOIN_LEFT;

const kJoinRight = SQL::JOIN_RIGHT;


