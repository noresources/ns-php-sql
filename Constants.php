<?php

namespace NoreSources\SQL;

class Constants
{
	/**
	 * Undefined value type
	 * @var integer
	 */
	const DATATYPE_UNDEFINED = 0x0;

	/**
	 * NULL value type
	 * @var integer
	 */
	const DATATYPE_NULL = 0x01;

	/**
	 * String value type
	 * @var integer
	 */
	const DATATYPE_STRING = 0x02;

	/**
	 * Integer value type
	 * @var integer
	 */
	const DATATYPE_INTEGER = 0x04;

	/**
	 * Float value type
	 * @var integer
	 */
	const DATATYPE_FLOAT = 0x08;

	/**
	 * Integer or float number.
	 * This constant value is a combination of
	 * @c DATATYPE_INTEGER and @c DATATYPE_FLOAT
	 * @var integer
	 */
	const DATATYPE_NUMBER = 0x0c;
	/**
	 * A string representing a Time stamp
	 * @var integer
	 */
	const DATATYPE_TIMESTAMP = 0x10;

	/**
	 * Boolean value type
	 * @var integer
	 */
	const DATATYPE_BOOLEAN = 0x20;

	/**
	 * Binary value type
	 * @var integer
	 */
	const DATATYPE_BINARY = 0x40;

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
	 * The type of Datasource column.
	 * Value type: integer
	 */
	const COLUMN_PROPERTY_DATA_TYPE = 'datatype';

	/**
	 * The column value is auto-incremented (integer column type only).
	 * Value type: boolean
	 */
	const COLUMN_PROPERTY_AUTOINCREMENT = 'autoincrement';

	/**
	 * The column accepts null values.
	 * Value type: boolean
	 */
	const COLUMN_PROPERTY_NULL = 'null';

	/**
	 * Data size.
	 *
	 * Value type: integer
	 */
	const COLUMN_PROPERTY_DATA_SIZE = 'datasize';

	/**
	 * Maximum number of digit to represents the fractional part of a floating-point number
	 * Value type: integer
	 */
	const COLUMN_PROPERTY_FRACTION_DIGIT_COUNT = 'fractiondigitcount';

	/**
	 * List of valid values.
	 * Value type: array
	 */
	const COLUMN_PROPERTY_ENUMERATION = 'enum';

	/**
	 * Default value.
	 * Value type: mixed
	 */
	const COLUMN_PROPERTY_DEFAULT_VALUE = 'default';

	// JOIN operator 
	const JOIN_NATURAL = 0x01;
	const JOIN_LEFT = 0x10;
	const JOIN_RIGHT = 0x20;
	const JOIN_INNER = 0x40;
	const JOIN_CROSS = 0x80;
	const JOIN_OUTER = 0x02;

	/**
	 * CURRENT_TIMESTAMP, CURRENT_TIMESTAMP() or NOW()
	 * @var integer
	 */
	const KEYWORD_CURRENT_TIMESTAMP = 0;

	/**
	 * NULL keyword
	 * @var integer
	 */
	const KEYWORD_NULL = 1;

	/**
	 * Ascending ORDER BY
	 * @var string
	 */
	const ORDERING_ASC = 'ASC';

	/**
	 * Descending ORDER BY
	 * @var string
	 */
	const ORDERING_DESC = 'DESC';

	/**
	 * Foreign key action "SET NULL".
	 * The constant value is used in StructureSerializer
	 * @var string
	 */
	const FOREIGN_KEY_ACTION_SET_NULL = 'null';

	/**
	 * Foreign key action "DEFAULT".
	 * The constant value is used in StructureSerializer
	 * @var string
	 */
	const FOREIGN_KEY_ACTION_SET_DEFAULT = 'default';

	/**
	 * Foreign key action "CASCADE"
	 * The constant value is used in StructureSerializer
	 * @var string
	 */
	const FOREIGN_KEY_ACTION_CASCADE = 'cascade';

	/**
	 * Foreign key action "RESTRICT".
	 * The constant value is used in StructureSerializer
	 * @var string
	 */
	const FOREIGN_KEY_ACTION_RESTRICT = 'restrict';

	/**
	 * Allow result column alias resolution in WHERE, HAVING and GROUP BY
	 * @var integer
	 */
	const BUILDER_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION = 0x01;

	/**
	 * INSERT INTO {table} DEFAULT VALUES syntax support
	 * @var integer
	 */
	const BUILDER_INSERT_DEFAULT_VALUES = 0x02;
	
	/**
	 * INSERT INTO {table} ({column}) VALUES (DEFAULT) syntax support
	 * @var integer
	 */
	const BUILDER_INSERT_DEFAULT_KEYWORD = 0x04;

	// Recordset flags

	/**
	 * Fetch record row to an associative array
	 * @var integer
	 */
	const RECORDSET_FETCH_ASSOCIATIVE = 0x01;

	/**
	 * Fetch record row to a indexed array
	 * @var integer
	 */
	const RECORDSET_FETCH_INDEXED = 0x02;

	/**
	 * Fetch record row to an array with both indexed and associative key
	 * @var integer
	 */
	const RECORDSET_FETCH_BOTH = 0x03;

	/**
	 * SQL structure description XML namespace URI
	 * @var string
	 */
	const XML_NAMESPACE_URI = 'http://xsd.nore.fr/sql';

	/**
	 * The XML namespace prefix used internally to reference the
	 * SQL structure description XML schema
	 * @var string
	 */
	const XML_NAMESPACE_PREFIX = 'sql';

	/**
	 * XML local name of the DatasourceStructure
	 * @var string
	 */
	const XML_ELEMENT_DATASOURCE = 'datasource';
	/**
	 * XML local name of the TableSetStructure
	 * @var string
	 */
	const XML_ELEMENT_TABLESET = 'database';
	/**
	 * XML local name of the TableStructure
	 * @var string
	 */
	const XML_ELEMENT_TABLE = 'table';

	/**
	 * XML local name of the TableColumnStructure
	 * @var string
	 */
	const XML_ELEMENT_COLUMN = 'column';

	/**
	 * Foreign key XML node name
	 * @var string
	 */
	const XML_ELEMENT_FOREIGN_KEY = 'foreignkey';

	/**
	 * Primary key XML node name
	 * @var string
	 */
	const XML_ELEMENT_PRIMARY_KEY = 'primarykey';

	/**
	 * Index XML node name
	 * @var string
	 */
	const XML_ELEMENT_UBDEX = 'index';
}
