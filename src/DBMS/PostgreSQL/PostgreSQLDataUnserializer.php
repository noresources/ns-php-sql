<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\PostgreSQL;

use NoreSources\SingletonTrait;
use NoreSources\TypeConversion;
use NoreSources\SQL\DataUnserializer;
use NoreSources\SQL\GenericDataUnserializerTrait;
use NoreSources\SQL\Structure\ColumnPropertyMap;

class PostgreSQLDataUnserializer implements DataUnserializer
{
	use GenericDataUnserializerTrait;

	use SingletonTrait;

	protected function unserializeBinaryColumnData(ColumnPropertyMap $column, $data)
	{
		return \pg_unescape_bytea($data);
	}

	protected function unserializeBooleanColumnData(ColumnPropertyMap $column, $data)
	{
		if (\is_string($data))
			return ($data == 't');
		return TypeConversion::toBoolean($data);
	}
}