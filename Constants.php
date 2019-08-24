<?php

namespace NoreSources\SQL;

class Constants
{
	const DATATYPE_UNDEFINED = 0x0;
	const DATATYPE_NULL = 0x01;
	const DATATYPE_STRING = 0x02;
	const DATATYPE_INTEGER = 0x04;
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
	const DATATYPE_BOOLEAN = 0x20;
	const DATATYPE_BINARY = 0x40;

	// DBMS types
	const CONNECTION_TYPE_VIRTUAL = 'cnx.virtual';
	const CONNECTION_TYPE_SQLITE = 'cnx.sqlite';
	const CONNECTION_TYPE_MYSQL = 'cnx.mysql';
	const CONNECTION_TYPE_POSTGRESQL = 'cnx.pgsql';

	// DBMS connection settings

	/**
	 * DBMS hostname, IP address or databalse file(s) path
	 * @var string
	 */
	const CONNECTION_PARAMETER_SOURCE = 'cnx.param.source';
	/**
	 * DBMS network port
	 * @var string
	 */
	const CONNECTION_PARAMETER_PORT = 'cnx.param.port';

	/**
	 * Account login
	 * @var string
	 */
	const CONNECTION_PARAMETER_USER = 'cnx.param.user';

	/**
	 * Account password
	 * @var string
	 */
	const CONNECTION_PARAMETER_PASSWORD = 'cnx.param.password';

	/**
	 * DBMS database.
	 * For DBMS with multiple database management such as PostgreSQL
	 * @var string
	 */
	const CONNECTION_PARAMETER_DATABASE = 'cnx.param.database';

	/**
	 * Create Database if it does not exists yer
	 * @var string
	 */
	const CONNECTION_PARAMETER_CREATE = 'cnx.param.create';

	/**
	 * Read-only access to database
	 * @var string
	 */
	const CONNECTION_PARAMETER_READONLY = 'cnx.param.readonly';

	/**
	 * SQLite file encryption key
	 * @var string
	 */
	const CONNECTION_PARAMETER_ENCRYPTION_KEY = 'cnx.param.encryptionkey';

	/**
	 * The type of Datasource column.
	 * Value type: integer
	 */
	const PROPERTY_COLUMN_DATA_TYPE = 'column.datatype';

	/**
	 * The column value is auto-incremented (integer column type only).
	 * Value type: boolean
	 */
	const PROPERTY_COLUMN_AUTOINCREMENT = 'column.autoincrement';

	/**
	 * The column accepts null values.
	 * Value type: boolean
	 */
	const PROPERTY_COLUMN_NULL = 'column.null';

	/**
	 * Data size.
	 *
	 * Value type: integer
	 */
	const PROPERTY_COLUMN_DATA_SIZE = 'column.datasize';

	/**
	 * Number of decimals (numeric field types).
	 * Value type: integer
	 */
	const PROPERTY_COLUMN_DECIMAL_COUNT = 'column.decimalsize';

	/**
	 * List of valid values.
	 * Value type: array
	 */
	const PROPERTY_COLUMN_ENUMERATION = 'column.enum';

	/**
	 * Default value.
	 * Value type: mixed
	 */
	const PROPERTY_COLUMN_DEFAULT_VALUE = 'column.default';

	// JOIN operator 
	const JOIN_NATURAL = 0x01;
	const JOIN_LEFT = 0x10;
	const JOIN_RIGHT = 0x20;
	const JOIN_INNER = 0x40;
	const JOIN_CROSS = 0x80;
	const JOIN_OUTER = 0x02;

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
	
	/**
	 * Foreign key constraint 
	 * @var string
	 */
	const CONSTRAINT_CONFLICT_ROLLBACK = 'constraint.rollback';
	const CONSTRAINT_CONFLICT_ABORT = 'constraint.abort';
	const CONSTRAINT_CONFLICT_FAIL = 'constraint.fail';
	const CONSTRAINT_CONFLICT_IGNORE = 'constraint.ignore';
	const CONSTRAINT_CONFLICT_REPLACE = 'constraint.replace';
	
	const FOREIGN_KEY_ACTION_SET_NULL = 'fk.action.null';
	const FOREIGN_KEY_ACTION_SET_DEFAULT = 'fk.action.default';
	const FOREIGN_KEY_ACTION_CASCADE = 'fk.action.cascade';
	const FOREIGN_KEY_ACTION_RESTRICT = 'fk.action.restrict';

	/**
	 * Allow result column alias resolution in WHERE, HAVING and GROUP BY
	 * @var integer
	 */
	const BUILDER_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION = 0x01;

	// Recordset flags
	const RECORDSET_FETCH_ASSOCIATIVE = 0x01;
	const RECORDSET_FETCH_INDEXED = 0x02;
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
