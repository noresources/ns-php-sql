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
use NoreSources\SQL\DBMS\DataUnserializerInterface;
use NoreSources\SQL\DBMS\Traits\DefaultDataUnserializerTrait;
use NoreSources\SQL\Structure\ColumnDescriptionInterface;

class PostgreSQLDataUnserializer implements DataUnserializerInterface
{
	use DefaultDataUnserializerTrait;

	use SingletonTrait;

	protected function unserializeBinaryColumnData(
		ColumnDescriptionInterface $column, $data)
	{
		return \pg_unescape_bytea($data);
	}

	protected function unserializeBooleanColumnData(
		ColumnDescriptionInterface $column, $data)
	{
		if (\is_string($data))
			return ($data == 't');
		return TypeConversion::toBoolean($data);
	}
}