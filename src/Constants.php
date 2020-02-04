<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;

class Constants
{

	/**
	 * Undefined value type
	 *
	 * @var integer
	 */
	const DATATYPE_UNDEFINED = 0x0;

	/**
	 * NULL value type
	 *
	 * @var integer
	 */
	const DATATYPE_NULL = 0x01;

	/**
	 * String value type
	 *
	 * @var integer
	 */
	const DATATYPE_STRING = 0x02;

	/**
	 * Integer value type
	 *
	 * @var integer
	 */
	const DATATYPE_INTEGER = 0x04;

	/**
	 * Float value type
	 *
	 * @var integer
	 */
	const DATATYPE_FLOAT = 0x08;

	/**
	 * Integer or float number.
	 * This constant value is a combination of
	 *
	 * @c DATATYPE_INTEGER and @c DATATYPE_FLOAT
	 * @var integer
	 */
	const DATATYPE_NUMBER = 0x0c;

	/**
	 * Date part of a timestamp type
	 *
	 * @var integer
	 */
	const DATATYPE_DATE = 0x10;

	/**
	 * Time part or a timestamp type
	 *
	 * @var integer
	 */
	const DATATYPE_TIME = 0x20;

	/**
	 * Date & time part of a timestamp
	 *
	 * @var integer
	 */
	const DATATYPE_DATETIME = 0x30;

	/**
	 * Time with timezone
	 *
	 * @var integer
	 */
	const DATATYPE_TIMEZONE = 0x60;

	/**
	 * A string representing a Time stamp
	 *
	 * @var integer
	 */
	const DATATYPE_TIMESTAMP = 0x70;

	/**
	 * Boolean value type
	 *
	 * @var integer
	 */
	const DATATYPE_BOOLEAN = 0x80;

	/**
	 * Binary value type
	 *
	 * @var integer
	 */
	const DATATYPE_BINARY = 0x100;

	// DBMS connection settings

	/**
	 * DBMS type
	 *
	 * @var string DBMS Connection class name or
	 *      one of the types registered using ConnectionHelper::registerConnectionClass()
	 */
	const CONNECTION_PARAMETER_TYPE = 'type';

	/**
	 * DBMS hostname, IP address or databalse file(s) path
	 *
	 * @var string
	 */
	const CONNECTION_PARAMETER_SOURCE = 'source';

	/**
	 * DBMS network port
	 *
	 * @var string
	 */
	const CONNECTION_PARAMETER_PORT = 'port';

	/**
	 * Account login
	 *
	 * @var string
	 */
	const CONNECTION_PARAMETER_USER = 'user';

	/**
	 * Account password
	 *
	 * @var string
	 */
	const CONNECTION_PARAMETER_PASSWORD = 'password';

	/**
	 * DBMS database.
	 * For DBMS with multiple database management such as PostgreSQL
	 *
	 * @var string
	 */
	const CONNECTION_PARAMETER_DATABASE = 'database';

	/**
	 * Create Database if it does not exists yer
	 *
	 * @var string
	 */
	const CONNECTION_PARAMETER_CREATE = 'create';

	/**
	 * Read-only access to database
	 *
	 * @var string
	 */
	const CONNECTION_PARAMETER_READONLY = 'readonly';

	/**
	 * StructureElement or file path of a strcture description
	 *
	 * @var string
	 */
	const CONNECTION_PARAMETER_STRUCTURE = 'structure';

	/**
	 * DBMS version.
	 * This may help Statement Builder togenerate a more accurate SQL string.
	 *
	 * @var string
	 */
	const CONNECTION_PARAMETER_VERSION = 'version';

	/**
	 * The type of Datasource column.
	 * Value type: integer
	 */
	const COLUMN_PROPERTY_DATA_TYPE = 'datatype';

	/**
	 * The column value is auto-incremented (integer column type only).
	 * Value type: boolean
	 */
	const COLUMN_PROPERTY_AUTO_INCREMENT = 'autoincrement';

	/**
	 * The column accepts null values.
	 * Value type: boolean
	 */
	const COLUMN_PROPERTY_ACCEPT_NULL = 'null';

	/**
	 * Maximum number of digits or characters
	 *
	 *
	 * Value type: integer
	 */
	const COLUMN_PROPERTY_GLYPH_COUNT = 'glyphcount';

	/**
	 * Maximum number of digit to represents the fractional part of a floating-point number
	 *
	 * Value type: integer
	 */
	const COLUMN_PROPERTY_FRACTION_SCALE = 'fractionscale';

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

	/**
	 * Define a custom deta unserializer for a column
	 *
	 * @var string
	 */
	const COLUMN_PROPERTY_UNSERIALIZER = 'unserializer';

	/**
	 * Column content media type as described in RFC 6838
	 *
	 * @see https://tools.ietf.org/html/rfc6838
	 *
	 * @var string
	 */
	const COLUMN_PROPERTY_MEDIA_TYPE = 'mediatype';

	/**
	 * Value padding
	 * <ul>
	 * <li>&lt; 0</li> Left padded
	 * <li>&gt; 0</li> Right padded
	 * <li>any other value </li> No padding
	 * </ul>
	 *
	 * @var string
	 */
	const COLUMN_PROPERTY_PADDING_DIRECTION = 'padding';

	/**
	 * Text pattern.
	 * Define string pattern that the value must match
	 *
	 * @var string
	 */
	const COLUMN_PROPERTY_TEXT_PATTERN = 'pattern';

	const TYPE_PROPERTY_DATA_TYPE = self::COLUMN_PROPERTY_DATA_TYPE;

	/**
	 * DBMS type name
	 *
	 * @var string
	 */
	const TYPE_PROPERTY_NAME = 'typename';

	/**
	 * DBMS type data size in bits.
	 *
	 * @var string
	 */
	const TYPE_PROPERTY_SIZE = 'typesize';

	/**
	 * Indicates if the DBMS type supports type length specification
	 *
	 * Property value is expected to be a boolean.
	 *
	 * @var string
	 */
	const TYPE_PROPERTY_GLYPH_COUNT = self::COLUMN_PROPERTY_GLYPH_COUNT;

	/**
	 * Indicates if the DBMS type supports fraction scale specification
	 *
	 * Property value is expected to be a boolean.
	 *
	 * @var unknown
	 */
	const TYPE_PROPERTY_FRACTION_SCALE = self::COLUMN_PROPERTY_FRACTION_SCALE;

	const TYPE_PROPERTY_MEDIA_TYPE = self::COLUMN_PROPERTY_MEDIA_TYPE;

	// JOIN operator
	const JOIN_NATURAL = 0x01;

	const JOIN_LEFT = 0x10;

	const JOIN_RIGHT = 0x20;

	const JOIN_INNER = 0x40;

	const JOIN_CROSS = 0x80;

	const JOIN_OUTER = 0x02;

	/**
	 * CURRENT_TIMESTAMP, CURRENT_TIMESTAMP() or NOW()
	 *
	 * @var integer
	 */
	const KEYWORD_CURRENT_TIMESTAMP = 0;

	/**
	 * NULL keyword
	 *
	 * @var integer
	 */
	const KEYWORD_NULL = 1;

	const KEYWORD_TRUE = 2;

	const KEYWORD_FALSE = 3;

	const KEYWORD_DEFAULT = 4;

	const KEYWORD_AUTOINCREMENT = 5;

	/**
	 * Ascending ORDER BY
	 *
	 * @var string
	 */
	const ORDERING_ASC = 'ASC';

	/**
	 * Descending ORDER BY
	 *
	 * @var string
	 */
	const ORDERING_DESC = 'DESC';

	/**
	 * Foreign key action "SET NULL".
	 * The constant value is used in StructureSerializer
	 *
	 * @var string
	 */
	const FOREIGN_KEY_ACTION_SET_NULL = 'null';

	/**
	 * Foreign key action "DEFAULT".
	 * The constant value is used in StructureSerializer
	 *
	 * @var string
	 */
	const FOREIGN_KEY_ACTION_SET_DEFAULT = 'default';

	/**
	 * Foreign key action "CASCADE"
	 * The constant value is used in StructureSerializer
	 *
	 * @var string
	 */
	const FOREIGN_KEY_ACTION_CASCADE = 'cascade';

	/**
	 * Foreign key action "RESTRICT".
	 * The constant value is used in StructureSerializer
	 *
	 * @var string
	 */
	const FOREIGN_KEY_ACTION_RESTRICT = 'restrict';

	const BUILDER_DOMAIN_GENERIC = 0;

	const BUILDER_IF_EXISTS = 0x01;

	const BUILDER_IF_NOT_EXISTS = 0x02;

	const BUILDER_DOMAIN_SELECT = 'select';

	/**
	 * Allow result column alias resolution in WHERE, HAVING and GROUP BY
	 *
	 * @var integer
	 */
	const BUILDER_SELECT_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION = 0x01000000;

	const BUILDER_DOMAIN_INSERT = 'insert';

	/**
	 * INSERT INTO {table} DEFAULT VALUES syntax support
	 *
	 * @var integer
	 */
	const BUILDER_INSERT_DEFAULT_VALUES = 0x01000000;

	/**
	 * INSERT INTO {table} ({column}) VALUES (DEFAULT) syntax support
	 *
	 * @var integer
	 */
	const BUILDER_INSERT_DEFAULT_KEYWORD = 0x02000000;

	const BUILDER_DOMAIN_UPDATE = 'update';

	const BUILDER_DOMAIN_DELETE = 'delete';

	const BUILDER_DOMAIN_DROP_TABLE = 'droptable';

	const BUILDER_DOMAIN_CREATE_TABLE = 'createtable';

	/**
	 * CREATE SCHEMA statements
	 *
	 * @var string
	 */
	const BUILDER_DOMAIN_CREATE_TABLESET = 'createtableset';

	// Tokens
	const TOKEN_SPACE = 0;

	const TOKEN_LITERAL = 1;

	const TOKEN_IDENTIFIER = 2;

	const TOKEN_KEYWORD = 3;

	const TOKEN_TEXT = 4;

	const TOKEN_PARAMETER = 5;

	// Query types
	const QUERY_SELECT = 0x01;

	const QUERY_INSERT = 0x02;

	const QUERY_UPDATE = 0x04;

	const QUERY_DELETE = 0x08;

	const QUERY_FAMILY_ROWMODIFICATION = 0x0C;

	const QUERY_CREATE_TABLE = 0x10;

	const QUERY_CREATE_INDEX = 0x20;

	const QUERY_FAMILY_CREATE = 0x30;

	// Recordset flags

	/**
	 * Fetch record row to an associative array
	 *
	 * @var integer
	 */
	const RECORDSET_FETCH_ASSOCIATIVE = 0x01;

	/**
	 * Fetch record row to a indexed array
	 *
	 * @var integer
	 */
	const RECORDSET_FETCH_INDEXED = 0x02;

	/**
	 * Fetch record row to an array with both indexed and associative key
	 *
	 * @var integer
	 */
	const RECORDSET_FETCH_BOTH = 0x03;

	/**
	 * Convert row values to the most accurate PHP object
	 * according result column type
	 *
	 * @var integer
	 */
	const RECORDSET_FETCH_UBSERIALIZE = 0x04;

	const RECORDSET_PUBLIC_FLAGS = 0x07;

	/**
	 * SQL structure description XML namespace URI prefix
	 *
	 * @var string
	 */
	const XML_NAMESPACE_BASEURI = 'http://xsd.nore.fr/sql';

	/**
	 * The XML namespace prefix used internally to reference the
	 * SQL structure description XML schema
	 *
	 * @var string
	 */
	const XML_NAMESPACE_PREFIX = 'sql';

	/**
	 * Index XML node name
	 *
	 * @var string
	 */
	const XML_ELEMENT_UBDEX = 'index';
}
