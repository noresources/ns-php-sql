<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\PostgreSQL;

use NoreSources\SingletonTrait;
use NoreSources\SQL\DBMS\DataUnserializerInterface;
use NoreSources\SQL\DBMS\Traits\DefaultDataUnserializerTrait;
use NoreSources\Type\TypeConversion;

class PostgreSQLDataUnserializer implements DataUnserializerInterface
{
	use DefaultDataUnserializerTrait;

	use SingletonTrait;

	protected function unserializeBinaryColumnData($column, $data)
	{
		return \pg_unescape_bytea($data);
	}

	protected function unserializeBooleanColumnData($column, $data)
	{
		if (\is_string($data))
			return ($data == 't');
		return TypeConversion::toBoolean($data);
	}
}