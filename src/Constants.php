<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\Bitset;
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
	const DATATYPE_UNDEFINED = Bitset::BIT_NONE;

	/**
	 * NULL value type
	 *
	 * @var integer
	 */
	const DATATYPE_NULL = Bitset::BIT_01;

	/**
	 * String value type
	 *
	 * @var integer
	 */
	const DATATYPE_STRING = Bitset::BIT_02;

	/**
	 * Integer value type
	 *
	 * @var integer
	 */
	const DATATYPE_INTEGER = Bitset::BIT_03;

	/**
	 * Float value type
	 *
	 * @var integer
	 */
	const DATATYPE_FLOAT = Bitset::BIT_04;

	/**
	 * Integer or float number.
	 * This constant value is a combination of
	 *
	 * @c DATATYPE_INTEGER and @c DATATYPE_FLOAT
	 * @var integer
	 */
	const DATATYPE_NUMBER = self::DATATYPE_INTEGER + self::DATATYPE_FLOAT;

	/**
	 * Date part of a timestamp type
	 *
	 * @var integer
	 */
	const DATATYPE_DATE = Bitset::BIT_05;

	/**
	 * Time part or a timestamp type
	 *
	 * @var integer
	 */
	const DATATYPE_TIME = Bitset::BIT_06;

	/**
	 * Date & time part of a timestamp
	 *
	 * @var integer
	 *
	 */
	const DATATYPE_DATETIME = self::DATATYPE_DATE + self::DATATYPE_TIME;

	/**
	 *
	 * Time with timezone
	 *
	 * @var integer
	 */
	const DATATYPE_TIMEZONE = Bitset::BIT_07;

	/**
	 * A string representing a Time stamp
	 *
	 * @var integer
	 */
	const DATATYPE_TIMESTAMP = self::DATATYPE_DATETIME +
		self::DATATYPE_TIMEZONE;

	/**
	 * Boolean value type
	 *
	 * @var integer
	 */
	const DATATYPE_BOOLEAN = Bitset::BIT_08;

	/**
	 * Binary value type
	 *
	 * @var integer
	 */
	const DATATYPE_BINARY = Bitset::BIT_09;

	// DBMS connection settings

	/**
	 * DBMS type
	 *
	 * @var string DBMS ConnectionInterface class name or
	 *      a key that one of ConnectionFactoryInterface implementation may understand.
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

	const CONNECTION_STRUCTURE_FILENAME_FACTORY = 'structurefilefactory';

	/**
	 * DBMS version.
	 * This may help Statement Builder togenerate a more accurate SQL string.
	 *
	 * @var string
	 */
	const CONNECTION_VERSION = 'version';

	/**
	 * Column name
	 *
	 * @var string
	 */
	const COLUMN_NAME = 'columnname';

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
	 * Column is auto incremented.
	 *
	 * For integer column only. On most DBMS, this property is also only available for primary key
	 * column
	 * and should appear only once per table.
	 *
	 * @var integer
	 */
	const COLUMN_FLAG_AUTO_INCREMENT = Bitset::BIT_02;

	/**
	 * Numeric value is always positive
	 *
	 * @var integer
	 */
	const COLUMN_FLAG_UNSIGNED = Bitset::BIT_03;

	/**
	 * Maximum or fixed number of elements that will represents a column value
	 *
	 * <ul>
	 * <li>Glyphs for string types</li>
	 * <li>Bytes for binary types</li>
	 * <li>Digits for number types</li>
	 * <li>Bits for bitset types</li>
	 * </ul>
	 *
	 *
	 * Value type: integer
	 */
	const COLUMN_LENGTH = 'length';

	/**
	 * Number column precision.
	 * Alias of COLUMN_LENGTH
	 */
	const COLUMN_PRECISION = self::COLUMN_LENGTH;

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

	/**
	 * Glyph used to pad value to the maximum length
	 *
	 * @var string
	 */
	const COLUMN_PADDING_GLYPH = 'paddingglyph';

	/**
	 * Padding direction
	 *
	 * <ul>
	 * <li>-1 Left</li>
	 * <li>1 Right</li>
	 * </ul>
	 */
	const COLUMN_PADDING_DIRECTION = 'paddingdirection';

	const COLUMN_PADDING_DIRECTION_LEFT = -1;

	const COLUMN_PADDING_DIRECTION_RIGHT = 1;

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
	 * Table column is part of a key constraint (index, primary key)
	 *
	 * @var integer
	 */
	const CONSTRAINT_COLUMN_KEY = Bitset::BIT_01;

	/**
	 * Table column uniqueness constraint.
	 *
	 * Returned by ColumnStructure::getConstraintFlags ()
	 */
	const CONSTRAINT_COLUMN_UNIQUE = Bitset::BIT_02 |
		self::CONSTRAINT_COLUMN_KEY;

	/**
	 * Table column primary key constraint.
	 *
	 * Returned by ColumnStructure::getConstraintFlags ()
	 */
	const CONSTRAINT_COLUMN_PRIMARY_KEY = Bitset::BIT_03 |
		self::CONSTRAINT_COLUMN_UNIQUE;

	const CONSTRAINT_COLUMN_PARTIAL = Bitset::BIT_04;

	/**
	 * DBMS type data type affinity
	 *
	 * Property value is expected to be an integer, combination of
	 * DATATYPE_* constants.
	 *
	 * @var integer
	 */
	const TYPE_DATA_TYPE = self::COLUMN_DATA_TYPE;

	/**
	 * Supported data type for the DEFAULT clause.
	 *
	 * If this property is not set, the TYPE_DATA_TYPE is used
	 *
	 * @var string
	 */
	const TYPE_DEFAULT_DATA_TYPE = 'typedefaultdatatype';

	/**
	 * DBMS type name
	 *
	 * @var string
	 */
	const TYPE_NAME = 'typename';

	/**
	 * DBMS type description
	 *
	 * @var string
	 */
	const TYPE_DESCRIPTION = 'typedescription';

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
	 * Indicates if the DBMS type supports length specification
	 * <ul>
	 * <li>glyph count for string types</li>
	 * <li>Precision for numeric type</li>
	 * </ul>
	 *
	 * @var number
	 */
	const TYPE_FLAG_LENGTH = Bitset::BIT_03;

	/**
	 * Indicates if the DBMS type supports fraction scale specification
	 * Implies TYPE_FLAG_LENGTH
	 *
	 * @var integer
	 */
	const TYPE_FLAG_FRACTION_SCALE = self::TYPE_FLAG_LENGTH +
		Bitset::BIT_04;

	/**
	 * Length specification in table column declaration
	 * to make the largest column length.
	 *
	 * @var number
	 */
	const TYPE_FLAG_MANDATORY_LENGTH = self::TYPE_FLAG_LENGTH |
		Bitset::BIT_05;

	/**
	 * Type accept signness specification (SIGNED / UNSIGNED)
	 *
	 * @var integer
	 */
	const TYPE_FLAG_SIGNNESS = Bitset::BIT_06;

	/**
	 * Maximum glyph count / length.
	 * Property value is expected to be a boolean.
	 *
	 * @var string
	 */
	const TYPE_MAX_LENGTH = 'maxlength';

	/**
	 * Column length if the length is unspecified
	 *
	 * In most case, a column without length specification
	 * is assumed to use the maximum length available for its type.
	 * Some rate types have another behavior.
	 *
	 * Property value is expected to be an integer.
	 *
	 * @var string
	 */
	const TYPE_DEFAULT_LENGTH = 'defaultlength';

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

	const TYPE_PADDING_DIRECTION_LEFT = self::COLUMN_PADDING_DIRECTION_LEFT;

	const TYPE_PADDING_DIRECTION_RIGHT = self::COLUMN_PADDING_DIRECTION_RIGHT;

	/**
	 * Value padding glyph.
	 * Implies the type have a non-zero padding direction
	 *
	 * @var string
	 */
	const TYPE_PADDING_GLYPH = self::COLUMN_PADDING_GLYPH;

	// JOIN operator
	const JOIN_NATURAL = Bitset::BIT_01;

	const JOIN_LEFT = Bitset::BIT_02;

	const JOIN_RIGHT = Bitset::BIT_03;

	const JOIN_INNER = Bitset::BIT_04;

	const JOIN_CROSS = Bitset::BIT_05;

	const JOIN_OUTER = Bitset::BIT_06;

	/**
	 * CURRENT_TIMESTAMP, CURRENT_TIMESTAMP() or NOW()
	 *
	 * @var integer
	 */
	const KEYWORD_CURRENT_TIMESTAMP = 1;

	/**
	 * NULL keyword
	 *
	 * @var integer
	 */
	const KEYWORD_NULL = 2;

	const KEYWORD_TRUE = 3;

	const KEYWORD_FALSE = 4;

	const KEYWORD_DEFAULT = 5;

	const KEYWORD_AUTOINCREMENT = 6;

	/**
	 * Name of the object containing table.
	 * This could be generally
	 * DATABASE or SCHEMA.
	 *
	 * @var string
	 */
	const KEYWORD_NAMESPACE = 7;

	/**
	 * TEMPORARY attribute used in temporary structure element creation.
	 *
	 * @var string
	 */
	const KEYWORD_TEMPORARY = 8;

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

	const EVENT_UPDATE = 'update';

	const EVENT_DELETE = 'delete';

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

	/**
	 * UNIQUE index
	 *
	 * @var integer
	 */
	const INDEX_UNIQUE = Bitset::BIT_01;

	// Tokens
	const TOKEN_SPACE = 0;

	const TOKEN_LITERAL = 1;

	const TOKEN_IDENTIFIER = 2;

	const TOKEN_KEYWORD = 3;

	const TOKEN_TEXT = 4;

	const TOKEN_PARAMETER = 5;

	const TOKEN_COMMENT = 6;

	const STATEMENT_RESULT_NONE = 0;

	const STATEMENT_RESULT_RECORDSET = Bitset::BIT_01;

	const STATEMENT_RESULT_INSERTION = Bitset::BIT_02;

	const STATEMENT_RESULT_ROWMODIFICATION = Bitset::BIT_03;

	// Query types

	/**
	 *
	 * @deprecated
	 */
	const QUERY_SELECT = Bitset::BIT_01;

	/**
	 *
	 * @deprecated
	 */
	const QUERY_INSERT = Bitset::BIT_02;

	/**
	 *
	 * @deprecated
	 */
	const QUERY_UPDATE = Bitset::BIT_03;

	/**
	 *
	 * @deprecated
	 */
	const QUERY_DELETE = Bitset::BIT_04;

	/**
	 *
	 * @deprecated
	 */
	const QUERY_FAMILY_ROWMODIFICATION = self::QUERY_UPDATE +
		self::QUERY_DELETE;

	/**
	 *
	 * @deprecated
	 */
	const QUERY_CREATE_TABLE = Bitset::BIT_05;

	/**
	 *
	 * @deprecated
	 */
	const QUERY_CREATE_INDEX = Bitset::BIT_06;

	/**
	 *
	 * @deprecated
	 */
	const QUERY_CREATE_NAMESPACE = Bitset::BIT_07;

	/**
	 *
	 * @deprecated
	 */
	const QUERY_CREATE_VIEW = Bitset::BIT_08;

	/**
	 *
	 * @deprecated
	 */
	const QUERY_FAMILY_CREATE = self::QUERY_CREATE_INDEX +
		self::QUERY_CREATE_NAMESPACE + self::QUERY_CREATE_TABLE +
		self::QUERY_CREATE_VIEW;

	/**
	 *
	 * @deprecated
	 */
	const QUERY_DROP_TABLE = Bitset::BIT_09;

	/**
	 *
	 * @deprecated
	 */
	const QUERY_DROP_INDEX = Bitset::BIT_10;

	/**
	 *
	 * @deprecated
	 */
	const QUERY_DROP_NAMESPACE = Bitset::BIT_11;

	/**
	 *
	 * @deprecated
	 */
	const QUERY_DROP_VIEW = Bitset::BIT_12;

	/**
	 *
	 * @deprecated
	 */
	const QUERY_FAMILY_DROP = self::QUERY_DROP_INDEX +
		self::QUERY_DROP_NAMESPACE + self::QUERY_DROP_TABLE +
		self::QUERY_DROP_VIEW;

	/**
	 *
	 * @deprecated
	 */
	const QUERY_FAMILY_STRUCTURE = self::QUERY_FAMILY_CREATE +
		self::QUERY_FAMILY_DROP;

	// SELECT query flags
	/**
	 * SELECT query flags.
	 *
	 * Only report distinct rows
	 *
	 * @var integer
	 */
	const SELECT_QUERY_DISTINCT = Bitset::BIT_01;

	/**
	 * CREATE query flags
	 *
	 * Create element only if not already exists.
	 *
	 * Require FEATURE_EXISTS_CONDITION support
	 *
	 * @var integer
	 */
	const CREATE_EXISTS_CONDITION = Bitset::BIT_01;

	/**
	 * CREATE query flags
	 *
	 * @var integer
	 */
	const CREATE_REPLACE = Bitset::BIT_02;

	/**
	 * CREATE query flags.
	 *
	 * Create a temporary element.
	 *
	 * @var integer
	 */
	const CREATE_TEMPORARY = Bitset::BIT_03;

	/**
	 * DROP query flags
	 *
	 * Drop only if element exists
	 */
	const DROP_EXISTS_CONDITION = Bitset::BIT_01;

	/**
	 * DROP query flags
	 *
	 * Drop element and all depending elements
	 */
	const DROP_CASCADE = Bitset::BIT_02;

	// Recordset flags

	/**
	 * Fetch record row to an associative array
	 *
	 * @var integer
	 */
	const RECORDSET_FETCH_ASSOCIATIVE = Bitset::BIT_01;

	/**
	 * Fetch record row to a indexed array
	 *
	 * @var integer
	 */
	const RECORDSET_FETCH_INDEXED = Bitset::BIT_02;

	/**
	 * Fetch record row to an array with both indexed and associative key
	 *
	 * @var integer
	 */
	const RECORDSET_FETCH_BOTH = self::RECORDSET_FETCH_ASSOCIATIVE +
		self::RECORDSET_FETCH_INDEXED;

	/**
	 * Convert row values to the most accurate PHP object
	 * according result column type
	 *
	 * @var integer
	 */
	const RECORDSET_FETCH_UBSERIALIZE = Bitset::BIT_03;

	/**
	 * RECORDSET_FETCH_UBSERIALIZE + RECORDSET_FETCH_BOTH
	 *
	 * @var number
	 */
	const RECORDSET_PUBLIC_FLAGS = self::RECORDSET_FETCH_BOTH +
		self::RECORDSET_FETCH_UBSERIALIZE;

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

	const PLATFORM_STRUCTURE_FILENAME_FACTORY = self::CONNECTION_STRUCTURE_FILENAME_FACTORY;

	/**
	 * DBMS current version
	 *
	 * @var string
	 */
	const PLATFORM_VERSION_CURRENT = 'current';

	/**
	 * The minimum DBMS version compatible with
	 * the current platform.
	 *
	 * @var string
	 */
	const PLATFORM_VERSION_COMPATIBILITY = 'compatibility';

	/**
	 * Platform feature doman.
	 *
	 * Namespace structure
	 *
	 * @var string
	 */
	const FEATURE_ELEMENT_NAMESPACE = 'namespace';

	/**
	 * Platform feature doman.
	 *
	 * Table structures.
	 *
	 * @var string
	 */
	const FEATURE_ELEMENT_TABLE = 'table';

	/**
	 * Platform feature doman.
	 *
	 * Table constraint structures.
	 *
	 * @var string
	 */
	const FEATURE_ELEMENT_TABLE_CONSTRAINT = 'tableconstraint';

	/**
	 * Platform feature doman.
	 *
	 * Column structures.
	 *
	 * @var string
	 */
	const FEATURE_ELEMENT_COLUMN = 'column';

	/**
	 * Platform feature doman.
	 *
	 * View declarations.
	 *
	 * @var string
	 */
	const FEATURE_ELEMENT_VIEW = 'view';

	/**
	 * Platform feature doman.
	 *
	 * Indexes
	 *
	 * @var string
	 */
	const FEATURE_ELEMENT_INDEX = 'index';

	/**
	 * Platform feature doman.
	 *
	 * Triggers
	 *
	 * @var string
	 */
	const FEATURE_ELEMENT_TRIGGER = 'trigger';

	/**
	 * Platform feature doman.
	 *
	 * Functions.
	 *
	 * @var string
	 */
	const FEATURE_ELEMENT_FUNCTION = 'function';

	/**
	 * Platform feature domain
	 *
	 * @var string
	 */
	const FEATURE_CREATE = 'create';

	/**
	 * Platform feature support.
	 *
	 * Standard creation option support flags
	 *
	 * @var string
	 */
	const FEATURE_CREATE_FLAGS = 'createflags';

	/**
	 * Platform feature support.
	 *
	 * OR REPLACE support in CREATE statements.
	 *
	 * @var string
	 *
	 */
	const FEATURE_CREATE_REPLACE = self::CREATE_REPLACE;

	/**
	 * Platform feature support.
	 *
	 * TEMPORARY structure creation support
	 */
	const FEATURE_CREATE_TEMPORARY = self::CREATE_TEMPORARY;

	const FEATURE_CREATE_EXISTS_CONDITION = self::CREATE_EXISTS_CONDITION;

	/**
	 * Platform feature support.
	 *
	 * Column declaration option flags
	 *
	 * @var string
	 */
	const FEATURE_COLUMN_DECLARATION_FLAGS = 'columndeclaration';

	/**
	 * Column declaration flags
	 *
	 * Column which are part of a key must have a length specification.
	 *
	 * @var number
	 */
	const FEATURE_COLUMN_KEY_MANDATORY_LENGTH = Bitset::BIT_01;

	/**
	 * Column declaration flags
	 *
	 * ENUM () support in column declaration.
	 *
	 * @var string
	 */
	const FEATURE_COLUMN_ENUM = Bitset::BIT_02;

	/**
	 * Constraint declaration flags
	 */
	const FEATURE_CONSTRAINT_DECLARATION_FLAGS = 'constraintdeclaration';

	/**
	 * Constraint declaration flag
	 *
	 * Force fully qualified REFERENCES
	 */
	const FEATURE_CONSTRAINT_REFERENCES_QUALIFIED = Bitset::BIT_01;

	/**
	 * Platform feature domain
	 *
	 * DROP statements
	 *
	 * @var string
	 */
	const FEATURE_DROP = 'drop';

	/**
	 * Platform feature domain.
	 */
	const FEATURE_DROP_FLAGS = 'dropflags';

	/**
	 * Platform feature domain
	 *
	 * CASCADE in DROP statements
	 *
	 * @var string
	 */
	const FEATURE_CASCADE = 'cascade';

	/**
	 * Platform feature support
	 *
	 * CASCADE modifier support for DROP statement
	 */
	const FEATURE_DROP_CASCADE = self::DROP_CASCADE;

	/**
	 * Platform feature support
	 *
	 * IF EXISTS modifier support in DROP statements
	 */
	const FEATURE_DROP_EXISTS_CONDITION = self::DROP_EXISTS_CONDITION;

	/**
	 * Platform feature domain.
	 *
	 * SELECT statements.
	 *
	 * @var string
	 */
	const FEATURE_SELECT = 'select';

	/**
	 * Platform feature support.
	 *
	 * Allow result column alias resolution in WHERE, HAVING and GROUP BY
	 *
	 * @var string
	 */
	const FEATURE_EXTENDED_RESULTCOLUMN_RESOLUTION = 'extendedresultcolumnresolution';

	/**
	 * DBMS natively support named parameters
	 *
	 * @var string
	 */
	const FEATURE_NAMED_PARAMETERS = 'namedparameters';

	/**
	 * Platform feature domain
	 *
	 * INSERT statements.
	 *
	 * @var string
	 */
	const FEATURE_INSERT = 'insert';

	/**
	 * Platform feature support
	 *
	 * INSERT query supported flags
	 *
	 * @var string
	 */
	const FEATURE_INSERT_FLAGS = 'insertflags';

	/**
	 * Platform feature support
	 *
	 * Support for DEFAULT VALUES in lieu of VALUES in INSERT statements.
	 *
	 * @var string
	 */
	const FEATURE_INSERT_FLAG_DEFAULTVALUES = Bitset::BIT_01;

	/**
	 * Platform feature support.
	 *
	 * DEFAULT keyword in VALUES() instructions of INSERT statements.
	 */
	const FEATURE_INSERT_FLAG_DEFAULT = Bitset::BIT_02;

	/**
	 * Platform feature support.
	 *
	 * INSERT INTO () SELECT support
	 *
	 * @var string
	 */
	const FEATURE_INSERT_FLAG_SELECT = Bitset::BIT_03;

	/**
	 * Platform feature support.
	 *
	 * List of supported JOIN types.
	 *
	 * Value is expected to be a combination of JOIN_* flags.
	 *
	 * @var string
	 */
	const FEATURE_JOINS = 'joins';

	/**
	 * Platform feature domain.
	 *
	 * Events / Triggers
	 *
	 * @var string
	 */
	const FEATURE_EVENTS = 'events';

	/**
	 * Platform feature support
	 *
	 * ON UPDATE event.
	 *
	 * @var string
	 */
	const FEATURE_EVENT_UPDATE = 'onupdate';

	/**
	 * Platform feature support
	 *
	 * ON DELETE event.
	 *
	 * @var string
	 */
	const FEATURE_EVENT_DELETE = 'ondelete';

	/**
	 *
	 * @var string
	 */
	const FEATURE_EVENT_ACTIONS = 'eventactions';

	/**
	 * Hexadecimal string media type
	 *
	 * @var string
	 */
	const MEDIA_TYPE_HEX_STRING = 'text/x.hexadecimal';

	/**
	 * Bit string media type
	 *
	 * @var string
	 */
	const MEDIA_TYPE_BIT_STRING = 'text/x.binary';

	/**
	 *
	 * @param integer $dataType
	 *        	Data type identifier
	 *
	 * @return string A string representation of the data type
	 */
	public static function dataTypeName($dataType)
	{
		if (($dataType & self::DATATYPE_NULL) &&
			(($dataType & self::DATATYPE_NULL) != self::DATATYPE_NULL))
			$dataType &= ~self::DATATYPE_NULL;

		$dataType &= ~self::DATATYPE_NULL;
		static $names = [
			self::DATATYPE_BINARY => 'binary',
			self::DATATYPE_BOOLEAN => 'boolean',
			self::DATATYPE_DATE => 'date',
			self::DATATYPE_TIME => 'time',
			(self::DATATYPE_TIME | self::DATATYPE_TIMEZONE) => 'time+tz',
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
