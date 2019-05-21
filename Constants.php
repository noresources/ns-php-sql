<?php

namespace NoreSources\SQL;

class Constants
{
	const kDataTypeUndefined = 0x0;
	const kDataTypeNull = 0x01;
	const kDataTypeString = 0x02;
	const kDataTypeInteger = 0x04;
	const kDataTypeDecimal = 0x08;
	const kDataTypeNumber = 0x0c; // 0x04 + 0x08
	const kDataTypeTimestamp = 0x10;
	const kDataTypeBoolean = 0x20;
	const kDataTypeBinary = 0x40;

	/**
	 * The type of Datasource column.
	 * Value type: integer
	 */
	const PROPERTY_COLUMN_DATA_TYPE = 'datasourcetype';

	/**
	 * The column is part of a primary key.
	 * Value type: boolean
	 */
	const PROPERTY_COLUMN_PRIMARYKEY = 'primary';
	
	/**
	 * @var string
	 * Value type: boolean
	 */
	const PROPERTY_COLUMN_FOREIGNKEY = 'foreign';
	
	/**
	 * The column value is auto-incremented (integer column type only).
	 * Value type: boolean
	 */
	const PROPERTY_COLUMN_AUTOINCREMENT = 'auto';
	
	/**
	 * The column is indexed.
	 * Value type: boolean
	 */
	const PROPERTY_COLUMN_INDEXED = 'index';

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
	
	const JOIN_NATURAL = 0x01;
	const JOIN_LEFT = 0x10;
	const JOIN_RIGHT = 0x20;
	const JOIN_INNER = 0x40;
	const JOIN_CROSS = 0x80;
	const JOIN_OUTER = 0x02;
	const ORDERING_ASC = 'ASC';
	const ORDERING_DESC = 'DESC';

	/**
	 * Allow result column alias resolution in WHERE, HAVING and GROUP BY
	 * @var integer
	 */
	const BUILDER_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION = 0x01;
	const CONSTRAINT_MODIFIER_AND = 1;
	const CONSTRAINT_MODIFIER_OR = 2;
	
	const STATEMENT_PARAMETER_SUBSTITUTION = 0x01;
}
const kDataTypeNull = 0x01;
const kDataTypeString = 0x02;
const kDataTypeInteger = 0x04;
const kDataTypeDecimal = 0x08;
const kDataTypeNumber = 0x0c; // 0x04 + 0x08
const kDataTypeTimestamp = 0x10;
const kDataTypeBoolean = 0x20;
const kDataTypeBinary = 0x40;
