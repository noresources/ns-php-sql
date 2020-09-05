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

use NoreSources\Container;

/**
 * Constant definition class.
 */
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
	 * @var string DBMS ConnectionInterface class name or
	 *      one of the types registered using ConnectionHelper::registerConnectionClass()
	 */
	const CONNECTION_TYPE = 'type';

	/**
	 * DBMS hostname, IP address or databalse file(s) path
	 *
	 * @var string
	 */
	const CONNECTION_SOURCE = 'source';

	/**
	 * DBMS network port
	 *
	 * @var string
	 */
	const CONNECTION_PORT = 'port';

	/**
	 * Account login
	 *
	 * @var string
	 */
	const CONNECTION_USER = 'user';

	/**
	 * Account password
	 *
	 * @var string
	 */
	const CONNECTION_PASSWORD = 'password';

	/**
	 * Use persistent connection if available.
	 *
	 * Value is expected to be a boolean.
	 *
	 * @var string
	 */
	const CONNECTION_PERSISTENT = 'persistent';

	/**
	 * Create Database if it does not exists yer
	 *
	 * @var string
	 */
	const CONNECTION_CREATE = 'create';

	/**
	 * Read-only access to database
	 *
	 * @var string
	 */
	const CONNECTION_READONLY = 'readonly';

	/**
	 * StructureElement or file path of a strcture description
	 *
	 * @var string
	 */
	const CONNECTION_STRUCTURE = 'structure';

	/**
	 * DBMS version.
	 * This may help Statement Builder togenerate a more accurate SQL string.
	 *
	 * @var string
	 */
	const CONNECTION_VERSION = 'version';

	/**
	 * The type of Datasource column.
	 * Value type: integer
	 */
	const COLUMN_DATA_TYPE = 'datatype';

	/**
	 * Column property flags
	 *
	 * @var string
	 */
	const COLUMN_FLAGS = 'volumnflags';

	/**
	 * Column accepts NULL as a valid value
	 *
	 * @var integer
	 */
	const COLUMN_FLAG_NULLABLE = 0x01;

	/**
	 * Column is auto incremented.
	 *
	 * For integer column only. On most DBMS, this property is also only available for primary key
	 * column
	 * and should appear only once per table.
	 *
	 * @var integer
	 */
	const COLUMN_FLAG_AUTO_INCREMENT = 0x02;

	/**
	 * Numeric value is always positive
	 *
	 * @var integer
	 */
	const COLUMN_FLAG_UNSIGNED = 0x04;

	const COLUMN_FLAGS_DEFAULT = self::COLUMN_FLAG_NULLABLE;

	/**
	 * Maximum number of digits or characters
	 *
	 *
	 * Value type: integer
	 */
	const COLUMN_LENGTH = 'length';

	/**
	 * Maximum number of digit to represents the fractional part of a floating-point number
	 *
	 * Value type: integer
	 */
	const COLUMN_FRACTION_SCALE = 'fractionscale';

	/**
	 * List of valid values.
	 * Value type: array
	 */
	const COLUMN_ENUMERATION = 'enum';

	/**
	 * Default value.
	 * Value type: mixed
	 */
	const COLUMN_DEFAULT_VALUE = 'default';

	const COLUMN_PADDING_DIRECTION = 'paddingdirection';

	const COLUMN_PADDING_GLYPH = 'paddingglyph';

	/**
	 * Define a custom deta unserializer for a column
	 *
	 * @var string
	 */
	const COLUMN_UNSERIALIZER = 'unserializer';

	/**
	 * Column content media type as described in RFC 6838
	 *
	 * @see https://tools.ietf.org/html/rfc6838
	 *
	 * @var string
	 */
	const COLUMN_MEDIA_TYPE = 'mediatype';

	/**
	 * DBMS type data type affinity
	 *
	 * Property value is expected to be an integer.
	 *
	 * @var integer
	 */
	const TYPE_DATA_TYPE = self::COLUMN_DATA_TYPE;

	/**
	 * DBMS type name
	 *
	 * @var string
	 */
	const TYPE_NAME = 'typename';

	/**
	 * DBMS type data size in bits.
	 * Property value is expected to be an integer.
	 *
	 * @var string
	 */
	const TYPE_SIZE = 'typesize';

	/**
	 * Type property flags
	 * Property value is expected to be an integer, combination of type flags.
	 *
	 * @var string
	 */
	const TYPE_FLAGS = 'typeflags';

	/**
	 * Type accepts default values
	 *
	 * @var integer
	 */
	const TYPE_FLAG_DEFAULT_VALUE = 0x01;

	/**
	 * Type accepts NULL values
	 *
	 * @var integer
	 */
	const TYPE_FLAG_NULLABLE = 0x02;

	/**
	 * Indicates if the DBMS type supports glyph count / length specification
	 *
	 * @var number
	 */
	const TYPE_FLAG_LENGTH = 0x04;

	/**
	 * Indicates if the DBMS type supports fraction scale specification
	 * Implies TYPE_FLAG_LENGTH
	 *
	 * @var integer
	 */
	const TYPE_FLAG_FRACTION_SCALE = 0x0c;

	/**
	 * Length specification in table column declaration
	 * to make the largest column length.
	 *
	 * @var number
	 */
	const TYPE_FLAG_MANDATORY_LENGTH = 0x10;

	/**
	 * Type accepts signness specification (SIGNED / UNSIGNED)
	 *
	 * @var integer
	 */
	const TYPE_FLAG_SIGNNESS = 0x20;

	/**
	 * Maximum glyph count / length.
	 * Property value is expected to be a boolean.
	 *
	 * @var string
	 */
	const TYPE_MAX_LENGTH = 'maxlength';

	/**
	 * Media Type
	 *
	 * Property value is expected to be a MediaTypeInterface or a Media Type compatible string
	 *
	 * @var string
	 */
	const TYPE_MEDIA_TYPE = self::COLUMN_MEDIA_TYPE;

	/**
	 * Value padding
	 * <ul>
	 * <li>&lt; 0</li> Left padded
	 * <li>&gt; 0</li> Right padded
	 * <li>any other value </li> No padding
	 * </ul>
	 *
	 * Implies the column value length is specified.
	 * . *
	 *
	 * @var string
	 */
	const TYPE_PADDING_DIRECTION = self::COLUMN_PADDING_DIRECTION;

	const TYPE_PADDING_DIRECTION_LEFT = -1;

	const TYPE_PADDING_DIRECTION_RIGHT = 1;

	/**
	 * Value padding glyph.
	 * Implies the type have a non-zero padding direction
	 *
	 * @var string
	 */
	const TYPE_PADDING_GLYPH = self::COLUMN_PADDING_GLYPH;

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
	const KEYWORD_CURRENT_TIMESTAMP = 'currenttimestamp';

	/**
	 * NULL keyword
	 *
	 * @var integer
	 */
	const KEYWORD_NULL = 'null';

	const KEYWORD_TRUE = 'true';

	const KEYWORD_FALSE = 'false';

	const KEYWORD_DEFAULT = 'default';

	const KEYWORD_AUTOINCREMENT = 'autoincrement';

	/**
	 * Name of the object containing table.
	 * This could be generally
	 * DATABASE or SCHEMA.
	 *
	 * @var string
	 */
	const KEYWORD_NAMESPACE = 'namespace';

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
	 * The constant value is used in StructureFileImporterInterface
	 *
	 * @var string
	 */
	const FOREIGN_KEY_ACTION_SET_NULL = 'null';

	/**
	 * Foreign key action "DEFAULT".
	 * The constant value is used in StructureFileImporterInterface
	 *
	 * @var string
	 */
	const FOREIGN_KEY_ACTION_SET_DEFAULT = 'default';

	/**
	 * Foreign key action "CASCADE"
	 * The constant value is used in StructureFileImporterInterface
	 *
	 * @var string
	 */
	const FOREIGN_KEY_ACTION_CASCADE = 'cascade';

	/**
	 * Foreign key action "RESTRICT".
	 * The constant value is used in StructureFileImporterInterface
	 *
	 * @var string
	 */
	const FOREIGN_KEY_ACTION_RESTRICT = 'restrict';

	const BUILDER_DOMAIN_GENERIC = 0;

	/**
	 * Builder supports the "DROM ...
	 * IF EXISTS" syntax.
	 *
	 * This flag could be defined in drop* domains.
	 *
	 * @var integer
	 */
	const BUILDER_IF_EXISTS = 0x01;

	/**
	 * Builder supports the "CREATE ...
	 * IF NOT EXISTS" syntax.
	 *
	 * This flag could be set in create* domains
	 *
	 * @var integer
	 */
	const BUILDER_IF_NOT_EXISTS = 0x02;

	/**
	 * Indicates the structure element
	 * can be declared inside a namesapce scope.
	 *
	 * This flag could be set for indexes, views etc.
	 *
	 * @var integer
	 */
	const BUILDER_SCOPED_STRUCTURE_DECLARATION = 0x04;

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

	/**
	 * CREATE SCHEMA / DATABASE statements
	 *
	 * @var string
	 */
	const BUILDER_DOMAIN_CREATE_NAMESPACE = 'createnamespace';

	const BUILDER_DOMAIN_DROP_NAMESPACE = 'dropnamespace';

	const BUILDER_DOMAIN_CREATE_TABLE = 'createtable';

	const BUILDER_DOMAIN_DROP_TABLE = 'droptable';

	const BUILDER_DOMAIN_CREATE_VIEW = 'createview';

	const BUILDER_DOMAIN_DROP_VIEW = 'dropview';

	const BUILDER_DOMAIN_CREATE_INDEX = 'createindex';

	const BUILDER_DOMAIN_DROP_INDEX = 'dropindex';

	/**
	 * Primary key column require a length specification.
	 *
	 * Usable in the following domains
	 * <ul>
	 * <li>BUILDER_DOMAIN_CREATE_TABLE</li>
	 * <li>BUILDER_DOMAIN_CREATE_INDEX</li>
	 * </ul>
	 *
	 * @var number
	 */
	const BUILDER_CREATE_COLUMN_KEY_MANDATORY_LENGTH = 0x01000000;

	/**
	 * Column description supports ENUM () syntax
	 *
	 * @var integer
	 */
	const BUILDER_CREATE_COLUMN_INLINE_ENUM = 0x02000000;

	/**
	 * CREATE "OR REPLACE" support
	 *
	 * @var number
	 */
	const BUILDER_CREATE_REPLACE = 0x04000000;

	/**
	 * CREATE "TEMPORARY" support
	 *
	 * @var number
	 */
	const BUILDER_CREATE_TEMPORARY = 0x08000000;

	/**
	 * DROP query supports CASCADE modifiers
	 *
	 * @var integer
	 */
	const BUILDER_DROP_CASCADE = 0x01000000;

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

	const QUERY_CREATE_TABLE = 0x0100;

	const QUERY_CREATE_INDEX = 0x0200;

	const QUERY_CREATE_NAMESPACE = 0x0400;

	const QUERY_CREATE_VIEW = 0x0800;

	const QUERY_FAMILY_CREATE = 0xFF00;

	const QUERY_DROP_TABLE = 0x010000;

	const QUERY_DROP_INDEX = 0x020000;

	const QUERY_DROP_NAMESPACE = 0x040000;

	const QUERY_DROP_VIEW = 0x080000;

	const QUERY_FAMILY_DROP = 0xFF0000;

	const QUERY_FAMILY_STRUCTURE = 0xFFFF00;

	// SELECT query flags
	/**
	 * SELECT qurey flags.
	 *
	 * Only report distinct rows
	 *
	 * @var integer
	 */
	const SELECT_QUERY_DISTINCT = 0x01;

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

	/**
	 * RECORDSET_FETCH_UBSERIALIZE + RECORDSET_FETCH_BOTH
	 *
	 * @var number
	 */
	const RECORDSET_PUBLIC_FLAGS = 0x07;

	/**
	 * Transaction block state.
	 *
	 * The transaction block is not initialized.
	 *
	 * @var integer
	 */
	const TRANSACTION_STATE_UNDEFINED = -1000;

	/**
	 * Transaction block state.
	 *
	 * The transaction block is in progress, waiting for commit or rollback.
	 *
	 * @var integer
	 */
	const TRANSACTION_STATE_PENDING = 0;

	/**
	 * Transaction block state.
	 *
	 * The transaction block was committed.
	 *
	 * @var integer
	 */
	const TRANSACTION_STATE_COMMITTED = 1;

	/**
	 * Transaction block state.
	 *
	 * The transaction block wass rolled back.
	 *
	 * @var integer
	 */
	const TRANSACTION_STATE_ROLLED_BACK = -1;

	/**
	 * Timestamp formatting meta function name.
	 *
	 * @var string
	 */
	const METAFUNCTION_TIMESTAMP_FORMAT = 'timestampformat';

	/**
	 *
	 * @param integer $dataType
	 *        	Data type identifier
	 *
	 * @return string A string representation of the data type
	 */
	public static function dataTypeName($dataType)
	{
		static $names = [
			self::DATATYPE_BINARY => 'binary',
			self::DATATYPE_BOOLEAN => 'boolean',
			self::DATATYPE_DATE => 'date',
			self::DATATYPE_TIME => 'time',
			self::DATATYPE_DATETIME => 'datetime',
			self::DATATYPE_TIMESTAMP => 'timestamp',
			self::DATATYPE_FLOAT => 'float',
			self::DATATYPE_INTEGER => 'integer',
			self::DATATYPE_NULL => 'null',
			self::DATATYPE_NUMBER => 'number',
			self::DATATYPE_STRING => 'string'
		];

		return Container::keyValue($names, $dataType, 'unknown');
	}

	public static function statementTypeName($statementType)
	{
		static $names = [
			self::QUERY_CREATE_INDEX => 'CREATE INDEX',
			self::QUERY_CREATE_TABLE => 'CREATE TABLE',
			self::QUERY_CREATE_NAMESPACE => 'CREATE NAMESPACE',
			self::QUERY_CREATE_VIEW => 'CREATE VIEW',

			self::QUERY_DELETE => 'DELETE',

			self::QUERY_DROP_INDEX => 'DROP INDEX',
			self::QUERY_DROP_TABLE => 'DROP TABLE',
			self::QUERY_DROP_NAMESPACE => 'DROP NAMESPACE',
			self::QUERY_DROP_VIEW => 'DROP VIEW',

			self::QUERY_INSERT => 'INSERT',

			self::QUERY_SELECT => 'SELECT',

			self::QUERY_UPDATE => 'UPDATE'
		];

		if (Container::keyExists($names, $statementType))
			return Container::keyValue($names, $statementType);

		static $families = [
			self::QUERY_FAMILY_CREATE => 'Creation',
			self::QUERY_FAMILY_DROP => 'Drop',
			self::QUERY_FAMILY_ROWMODIFICATION => 'Row modification',
			self::QUERY_FAMILY_STRUCTURE => 'Structure'
		];

		foreach ($families as $family => $name)
		{
			if ($statementType & $family)
				return $name;
		}

		return $statementType;
	}
}
