<?php

namespace NoreSources\SQL;

class K
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
	
	const JOIN_NATURAL 	= 	0x01;
	const JOIN_LEFT 	= 	0x10;
	const JOIN_RIGHT 	= 	0x20;
	const JOIN_INNER 	= 	0x40;
	const JOIN_CROSS 	= 	0x80;
	const JOIN_OUTER 	= 	0x02;
	
	const ORDERING_ASC = 'ASC';
	const ORDERING_DESC = 'DESC';
	
	/**
	 * Allow result column alias resolution in WHERE, HAVING and GROUP BY
	 * @var integer
	 */
	const BUILDER_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION = 0x01;

	const CONSTRAINT_MODIFIER_AND 	= 1;
	const CONSTRAINT_MODIFIER_OR 	= 2;
}

const kDataTypeNull = 0x01;
const kDataTypeString = 0x02;
const kDataTypeInteger = 0x04;
const kDataTypeDecimal = 0x08;
const kDataTypeNumber = 0x0c; // 0x04 + 0x08
const kDataTypeTimestamp = 0x10;
const kDataTypeBoolean = 0x20;
const kDataTypeBinary = 0x40;
