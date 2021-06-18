<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\PostgreSQL;

use NoreSources\SingletonTrait;
use NoreSources\SQL\DBMS\TypeRegistry;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConstants as K;
use NoreSources\SQL\DBMS\Types\ArrayObjectType;

class PostgreSQLTypeRegistry extends TypeRegistry
{

	use SingletonTrait;

	public function __construct()
	{
		parent::__construct(
			[ /* --<typeProperties>-- */
				'boolean' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'boolean',
						K::TYPE_DATA_TYPE => K::DATATYPE_BOOLEAN,
						K::TYPE_SIZE => 8
					]),
				'bigint' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'bigint',
						K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
						K::TYPE_SIZE => 64
					]),
				'bit varying' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'bit varying',
						K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
						K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH),
						K::TYPE_MEDIA_TYPE => K::MEDIA_TYPE_BIT_STRING
					]),
				'bytea' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'bytea',
						K::TYPE_DATA_TYPE => K::DATATYPE_BINARY
					]),
				'char' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'char',
						K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
						K::TYPE_MAX_LENGTH => 10485760,
						K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH),
						K::TYPE_PADDING_DIRECTION => K::TYPE_PADDING_DIRECTION_RIGHT,
						K::TYPE_PADDING_GLYPH => ' ',
						K::TYPE_SIZE => 8
					]),
				'character varying' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'character varying',
						K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
						K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH),
						K::TYPE_MAX_LENGTH => 10485760
					]),
				'date' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'date',
						K::TYPE_DATA_TYPE => K::DATATYPE_DATE,
						K::TYPE_SIZE => 32,
						K::TYPE_DEFAULT_DATA_TYPE => 112
					]),
				'double precision' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'double precision',
						K::TYPE_DATA_TYPE => K::DATATYPE_FLOAT,
						K::TYPE_SIZE => 64
					]),
				'integer' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'integer',
						K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
						K::TYPE_SIZE => 32
					]),
				'json' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'json',
						K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
						K::TYPE_MEDIA_TYPE => 'application/json'
					]),
				'jsonb' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'jsonb',
						K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
						K::TYPE_MEDIA_TYPE => 'application/json'
					]),
				'numeric' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'numeric',
						K::TYPE_DATA_TYPE => K::DATATYPE_NUMBER,
						K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH |
						K::TYPE_FLAG_FRACTION_SCALE)
					]),
				'real' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'real',
						K::TYPE_DATA_TYPE => K::DATATYPE_FLOAT,
						K::TYPE_SIZE => 32
					]),
				'smallint' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'smallint',
						K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
						K::TYPE_SIZE => 16
					]),
				'text' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'text',
						K::TYPE_DATA_TYPE => K::DATATYPE_STRING
					]),
				'time without time zone' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'time without time zone',
						K::TYPE_DATA_TYPE => K::DATATYPE_TIME,
						K::TYPE_SIZE => 64,
						K::TYPE_DEFAULT_DATA_TYPE => 112
					]),
				'timestamp without time zone' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'timestamp without time zone',
						K::TYPE_DATA_TYPE => K::DATATYPE_DATETIME,
						K::TYPE_SIZE => 64,
						K::TYPE_DEFAULT_DATA_TYPE => 112
					]),
				'timestamp with time zone' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'timestamp with time zone',
						K::TYPE_DATA_TYPE => K::DATATYPE_TIMESTAMP,
						K::TYPE_SIZE => 64,
						K::TYPE_DEFAULT_DATA_TYPE => 112
					]),
				'time with time zone' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'time with time zone',
						K::TYPE_DATA_TYPE => (K::DATATYPE_UNDEFINED |
						K::DATATYPE_TIME | K::DATATYPE_TIMEZONE),
						K::TYPE_SIZE => 96,
						K::TYPE_DEFAULT_DATA_TYPE => 112
					]),
				'xml' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'xml',
						K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
						K::TYPE_MEDIA_TYPE => 'text/xml'
					])
				/* --</typeProperties>-- */
			]);
	}
}