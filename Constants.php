<?php

namespace NoreSources\SQL;

class Constants
{
	const kDataTypeUndefined = 0x0;
	const kDataTypeNull = 0x01;
	const kDataTypeString = 0x02;
	const kDataTypeInteger = 0x04;
	const kDataTypeFloat = 0x08;
	const kDataTypeNumber = 0x0c; // 0x04 + 0x08
	const kDataTypeTimestamp = 0x10;
	const kDataTypeBoolean = 0x20;
	const kDataTypeBinary = 0x40;

	// DBMS types
	const CONNECTION_TYPE_VIRTUAL = 'virtual';
	const CONNECTION_TYPE_SQLITE = 'sqlite';
	const CONNECTION_TYPE_MYSQL = 'mysql';
	const CONNECTION_TYPE_POSTGRESQL = 'pgsql';

	// DBMS connection settings

	/**
	 * DBMS hostname, IP address or databalse file(s) path
	 * @var string
	 */
	const CONNECTION_PARAMETER_SOURCE = 'source';
	/**
	 * DBMS network port
	 * @var string
	 */
	const CONNECTION_PARAMETER_PORT = 'port';

	/**
	 * Account login
	 * @var string
	 */
	const CONNECTION_PARAMETER_USER = 'user';

	/**
	 * Account password
	 * @var string
	 */
	const CONNECTION_PARAMETER_PASSWORD = 'password';

	/**
	 * DBMS database.
	 * For DBMS with multiple database management such as PostgreSQL
	 * @var string
	 */
	const CONNECTION_PARAMETER_DATABASE = 'database';

	/**
	 * Create Database if it does not exists yer
	 * @var string
	 */
	const CONNECTION_PARAMETER_CREATE = 'create';

	/**
	 * Read-only access to database
	 * @var string
	 */
	const CONNECTION_PARAMETER_READONLY = 'readonly';

	/**
	 * SQLite file encryption key
	 * @var string
	 */
	const CONNECTION_PARAMETER_ENCRYPTION_KEY = 'encryptionkey';

	/**
	 * The type of Datasource column.
	 * Value type: integer
	 */
	const PROPERTY_COLUMN_DATA_TYPE = 'datatype';

	/**
	 * The column value is auto-incremented (integer column type only).
	 * Value type: boolean
	 */
	const PROPERTY_COLUMN_AUTOINCREMENT = 'auto';

	/**
	 * The column accepts null values.
	 * Value type: boolean
	 */
	const PROPERTY_COLUMN_NULL = 'null';

	/**
	 * Data size.
	 *
	 * Value type: integer
	 */
	const PROPERTY_COLUMN_DATA_SIZE = 'size';

	/**
	 * Number of decimals (numeric field types).
	 * Value type: integer
	 */
	const PROPERTY_COLUMN_DECIMAL_COUNT = 'decimalsize';

	/**
	 * List of valid values.
	 * Value type: array
	 */
	const PROPERTY_COLUMN_ENUMERATION = 'valid_values';

	/**
	 * Default value.
	 * Value type: mixed
	 */
	const PROPERTY_COLUMN_DEFAULT_VALUE = 'default_value';

	// JOIN operator 
	const JOIN_NATURAL = 0x01;
	const JOIN_LEFT = 0x10;
	const JOIN_RIGHT = 0x20;
	const JOIN_INNER = 0x40;
	const JOIN_CROSS = 0x80;
	const JOIN_OUTER = 0x02;
	const ORDERING_ASC = 'ASC';
	const ORDERING_DESC = 'DESC';

	/**
	 * Uniqueness constraint type
	 * @var integer
	 */
	const TABLE_CONSTRAINT_UNIQUE = 0x01;
	
	/**
	 * Primary key constraint type
	 * @var integer
	 */
	const TABLE_CONSTRAINT_PRIMARY_KEY = 0x03;
	
	const TABLE_CONSTRAINT_FOREIGN_KEY = 0x04;
	
	const CONSTRAINT_CONFLICT_ROLLBACK = 'rollback';
	const CONSTRAINT_CONFLICT_ABORT = 'abort';
	const CONSTRAINT_CONFLICT_FAIL = 'fail';
	const CONSTRAINT_CONFLICT_IGNORE = 'ignore';
	const CONSTRAINT_CONFLICT_REPLACE = 'replace';
	
	const FOREIGN_KEY_ACTION_SET_NULL = ' null';
	const FOREIGN_KEY_ACTION_SET_DEFAULT = 'default';
	const FOREIGN_KEY_ACTION_CASCADE = 'cascade';
	const FOREIGN_KEY_ACTION_RESTRICT = 'restrict';
		
	/**
	 * Allow result column alias resolution in WHERE, HAVING and GROUP BY
	 * @var integer
	 */
	const BUILDER_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION = 0x01;
	
	// Recordset flags
	
	const RECORDSET_FETCH_ASSOCIATIVE = 0x01;
	const RECORDSET_FETCH_INDEXED = 0x02;
	const RECORDSET_FETCH_BOTH = 0x03;

	const XML_NAMESPACE_URI = 'http://xsd.nore.fr/sql';
	const XML_NAMESPACE_PREFIX = 'sql';
	const XML_ELEMENT_DATASOURCE = 'datasource';
	const XML_ELEMENT_TABLESET = 'database';
	const XML_ELEMENT_TABLE = 'table';
	const XML_ELEMENT_COLUMN = 'column';
	const XML_ELEMENT_FOREIGN_KEY = 'foreignkey';
	const XML_ELEMENT_PRIMARY_KEY = 'primarykey';
	const XML_ELEMENT_UBDEX = 'index';
}


const kDataTypeNull = 0x01;
const kDataTypeString = 0x02;
const kDataTypeInteger = 0x04;
const kDataTypeFloat = 0x08;
const kDataTypeNumber = 0x0c; // 0x04 + 0x08
const kDataTypeTimestamp = 0x10;
const kDataTypeBoolean = 0x20;
const kDataTypeBinary = 0x40;
