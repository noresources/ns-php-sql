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
						'typename' => 'boolean',
						K::TYPE_DATA_TYPE => K::DATATYPE_BOOLEAN,
						'typesize' => 8
					]),
				'bigint' => new ArrayObjectType(
					[
						'typename' => 'bigint',
						K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
						'typesize' => 64
					]),
				'bit varying' => new ArrayObjectType(
					[
						'typename' => 'bit varying',
						K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
						K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH),
						'mediatype' => K::MEDIA_TYPE_BIT_STRING
					]),
				'bytea' => new ArrayObjectType(
					[
						'typename' => 'bytea',
						K::TYPE_DATA_TYPE => K::DATATYPE_BINARY
					]),
				'char' => new ArrayObjectType(
					[
						'typename' => 'char',
						K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
						'maxlength' => 1,
						'typesize' => 8
					]),
				'character varying' => new ArrayObjectType(
					[
						'typename' => 'character varying',
						K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
						K::TYPE_FLAGS => (K::TYPE_FLAG_LENGTH)
					]),
				'date' => new ArrayObjectType(
					[
						'typename' => 'date',
						K::TYPE_DATA_TYPE => K::DATATYPE_DATE,
						'typesize' => 32,
						'typedefaultdatatype' => 112
					]),
				'double precision' => new ArrayObjectType(
					[
						'typename' => 'double precision',
						K::TYPE_DATA_TYPE => K::DATATYPE_FLOAT,
						'typesize' => 64
					]),
				'integer' => new ArrayObjectType(
					[
						'typename' => 'integer',
						K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
						'typesize' => 32
					]),
				'json' => new ArrayObjectType(
					[
						'typename' => 'json',
						K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
						'mediatype' => 'application/json'
					]),
				'jsonb' => new ArrayObjectType(
					[
						'typename' => 'jsonb',
						K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
						'mediatype' => 'application/json'
					]),
				'numeric' => new ArrayObjectType(
					[
						'typename' => 'numeric',
						K::TYPE_DATA_TYPE => K::DATATYPE_NUMBER,
						K::TYPE_FLAGS => (K::TYPE_FLAG_FRACTION_SCALE |
						K::TYPE_FLAG_LENGTH)
					]),
				'real' => new ArrayObjectType(
					[
						'typename' => 'real',
						K::TYPE_DATA_TYPE => K::DATATYPE_FLOAT,
						'typesize' => 32
					]),
				'smallint' => new ArrayObjectType(
					[
						'typename' => 'smallint',
						K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
						'typesize' => 16
					]),
				'text' => new ArrayObjectType(
					[
						'typename' => 'text',
						K::TYPE_DATA_TYPE => K::DATATYPE_STRING
					]),
				'time without time zone' => new ArrayObjectType(
					[
						'typename' => 'time without time zone',
						K::TYPE_DATA_TYPE => K::DATATYPE_TIME,
						'typesize' => 64,
						'typedefaultdatatype' => 112
					]),
				'timestamp without time zone' => new ArrayObjectType(
					[
						'typename' => 'timestamp without time zone',
						K::TYPE_DATA_TYPE => K::DATATYPE_DATETIME,
						'typesize' => 64,
						'typedefaultdatatype' => 112
					]),
				'timestamp with time zone' => new ArrayObjectType(
					[
						'typename' => 'timestamp with time zone',
						K::TYPE_DATA_TYPE => K::DATATYPE_TIMESTAMP,
						'typesize' => 64,
						'typedefaultdatatype' => 112
					]),
				'time with time zone' => new ArrayObjectType(
					[
						'typename' => 'time with time zone',
						K::TYPE_DATA_TYPE => (K::DATATYPE_TIME |
						K::DATATYPE_TIMEZONE | K::DATATYPE_UNDEFINED),
						'typesize' => 96,
						'typedefaultdatatype' => 112
					]),
				'xml' => new ArrayObjectType(
					[
						'typename' => 'xml',
						K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
						'mediatype' => 'text/xml'
					])
				/* --</typeProperties>-- */
			]);
	}
}