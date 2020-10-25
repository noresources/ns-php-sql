<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
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
		$this->typeNameOidMap = [ /* Auto-generated code --<typeNameOidMap>-- */
'abstime' => 702,
'aclitem' => 1033,
'"any"' => 2276,
'anyarray' => 2277,
'anyelement' => 2283,
'anyenum' => 3500,
'anynonarray' => 2776,
'anyrange' => 3831,
'bigint' => 20,
'bit' => 1560,
'bit varying' => 1562,
'boolean' => 16,
'box' => 603,
'bytea' => 17,
'"char"' => 18,
'character' => 1042,
'character varying' => 1043,
'cid' => 29,
'cidr' => 650,
'circle' => 718,
'cstring' => 2275,
'date' => 1082,
'daterange' => 3912,
'double precision' => 701,
'event_trigger' => 3838,
'fdw_handler' => 3115,
'gtsvector' => 3642,
'index_am_handler' => 325,
'inet' => 869,
'int2vector' => 22,
'int4range' => 3904,
'int8range' => 3926,
'integer' => 23,
'internal' => 2281,
'interval' => 1186,
'json' => 114,
'jsonb' => 3802,
'language_handler' => 2280,
'line' => 628,
'lseg' => 601,
'macaddr' => 829,
'macaddr8' => 774,
'money' => 790,
'name' => 19,
'numeric' => 1700,
'numrange' => 3906,
'odd' => 119149,
'oid' => 26,
'oidvector' => 30,
'opaque' => 2282,
'path' => 602,
'pg_ddl_command' => 32,
'pg_dependencies' => 3402,
'pg_lsn' => 3220,
'pg_ndistinct' => 3361,
'pg_node_tree' => 194,
'point' => 600,
'polygon' => 604,
'real' => 700,
'record' => 2249,
'refcursor' => 1790,
'regclass' => 2205,
'regconfig' => 3734,
'regdictionary' => 3769,
'regnamespace' => 4089,
'regoper' => 2203,
'regoperator' => 2204,
'regproc' => 24,
'regprocedure' => 2202,
'regrole' => 4096,
'regtype' => 2206,
'reltime' => 703,
'smallint' => 21,
'smgr' => 210,
'text' => 25,
'tid' => 27,
'timestamp without time zone' => 1114,
'timestamp with time zone' => 1184,
'time without time zone' => 1083,
'time with time zone' => 1266,
'tinterval' => 704,
'trigger' => 2279,
'tsm_handler' => 3310,
'tsquery' => 3615,
'tsrange' => 3908,
'tstzrange' => 3910,
'tsvector' => 3614,
'txid_snapshot' => 2970,
'unknown' => 705,
'uuid' => 2950,
'void' => 2278,
'xid' => 28,
'xml' => 142
/* --</typeNameOidMap>-- */
		];

		parent::__construct(
			[ /* --<typeProperties>-- */
'boolean' => new ArrayObjectType([ 
'typename' => 'boolean',
'datatype' => 128, 
'typesize' => 8, 
]),
'bigint' => new ArrayObjectType([ 
'typename' => 'bigint',
'datatype' => 4, 
'typesize' => 64, 
]),
'bit varying' => new ArrayObjectType([ 
'typename' => 'bit varying',
'datatype' => 2, 
'typeflags' => 7, 
'mediatype' => 	K::MEDIA_TYPE_BIT_STRING, 
]),
'bytea' => new ArrayObjectType([ 
'typename' => 'bytea',
'datatype' => 256, 
]),
'char' => new ArrayObjectType([ 
'typename' => 'char',
'datatype' => 2, 
'maxlength' => 1, 
'typesize' => 8, 
]),
'character varying' => new ArrayObjectType([ 
'typename' => 'character varying',
'datatype' => 2, 
'typeflags' => 7, 
]),
'date' => new ArrayObjectType([ 
'typename' => 'date',
'datatype' => 16, 
'typesize' => 32, 
'typedefaultdatatype' => 112, 
]),
'double precision' => new ArrayObjectType([ 
'typename' => 'double precision',
'datatype' => 8, 
'typesize' => 64, 
]),
'integer' => new ArrayObjectType([ 
'typename' => 'integer',
'datatype' => 4, 
'typesize' => 32, 
]),
'json' => new ArrayObjectType([ 
'typename' => 'json',
'datatype' => 2, 
'mediatype' => 'application/json', 
]),
'jsonb' => new ArrayObjectType([ 
'typename' => 'jsonb',
'datatype' => 256, 
'mediatype' => 'application/json', 
]),
'numeric' => new ArrayObjectType([ 
'typename' => 'numeric',
'datatype' => 12, 
'typeflags' => 15, 
]),
'real' => new ArrayObjectType([ 
'typename' => 'real',
'datatype' => 8, 
'typesize' => 32, 
]),
'smallint' => new ArrayObjectType([ 
'typename' => 'smallint',
'datatype' => 4, 
'typesize' => 16, 
]),
'text' => new ArrayObjectType([ 
'typename' => 'text',
'datatype' => 2, 
]),
'time without time zone' => new ArrayObjectType([ 
'typename' => 'time without time zone',
'datatype' => 32, 
'typesize' => 64, 
'typedefaultdatatype' => 112, 
]),
'timestamp without time zone' => new ArrayObjectType([ 
'typename' => 'timestamp without time zone',
'datatype' => 48, 
'typesize' => 64, 
'typedefaultdatatype' => 112, 
]),
'timestamp with time zone' => new ArrayObjectType([ 
'typename' => 'timestamp with time zone',
'datatype' => 112, 
'typesize' => 64, 
'typedefaultdatatype' => 112, 
]),
'time with time zone' => new ArrayObjectType([ 
'typename' => 'time with time zone',
'datatype' => 96, 
'typesize' => 96, 
'typedefaultdatatype' => 112, 
]),
'xml' => new ArrayObjectType([ 
'typename' => 'xml',
'datatype' => 2, 
'mediatype' => 'text/xml', 
])
/* --</typeProperties>-- */
			]);
	}

	/**
	 *
	 * @var array
	 */
	private $typeNameOidMap;
}