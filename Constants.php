<?php

namespace NoreSources\SQL;

class K
{
	const kDataTypeNull = 0x01;
	const kDataTypeString = 0x02;
	const kDataTypeInteger = 0x04;
	const kDataTypeDecimal = 0x08;
	const kDataTypeNumber = 0x0c; // 0x04 + 0x08
	const kDataTypeTimestamp = 0x10;
	const kDataTypeBoolean = 0x20;
	const kDataTypeBinary = 0x40;
	
	const JOIN_INNER = 	0x01;
	const JOIN_OUTER = 	0x02;
	const JOIN_CROSS = 	0x04;
	const JOIN_LEFT = 	0x10;
	const JOIN_RIGHT = 	0x20;
}

const kDataTypeNull = 0x01;
const kDataTypeString = 0x02;
const kDataTypeInteger = 0x04;
const kDataTypeDecimal = 0x08;
const kDataTypeNumber = 0x0c; // 0x04 + 0x08
const kDataTypeTimestamp = 0x10;
const kDataTypeBoolean = 0x20;
const kDataTypeBinary = 0x40;
