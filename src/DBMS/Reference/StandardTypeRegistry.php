<?php
namespace NoreSources\SQL\DBMS\Reference;

use NoreSources\SingletonTrait;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\ArrayObjectType;
use NoreSources\SQL\DBMS\TypeHelper;
use NoreSources\SQL\DBMS\TypeRegistry;

class StandardTypeRegistry extends TypeRegistry
{

	use SingletonTrait;

	public function __construct()
	{
		parent::__construct(
			[
				'char' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'CHAR',
						K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
						K::TYPE_FLAGS => TypeHelper::getDefaultTypeProperty(
							K::TYPE_FLAGS) |
						K::TYPE_FLAG_MANDATORY_LENGTH,
						K::TYPE_PADDING_GLYPH => ' ',
						K::TYPE_PADDING_DIRECTION => K::TYPE_PADDING_DIRECTION_RIGHT,
						K::TYPE_MAX_LENGTH => 255
					]),
				'varchar' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'VARCHAR',
						K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
						K::TYPE_FLAGS => TypeHelper::getDefaultTypeProperty(
							K::TYPE_FLAGS) | K::TYPE_FLAG_LENGTH,
						K::TYPE_MAX_LENGTH => 255
					]),
				'clob' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'CLOB',
						K::TYPE_DATA_TYPE => K::DATATYPE_STRING
					]),
				'binary' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'BINARY',
						K::TYPE_DATA_TYPE => K::DATATYPE_BINARY
					]),
				'blob' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'BLOB',
						K::TYPE_DATA_TYPE => K::DATATYPE_BINARY
					]),
				'varbinary' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'VARBINARY',
						K::TYPE_DATA_TYPE => K::DATATYPE_BINARY,
						K::TYPE_FLAGS => TypeHelper::getDefaultTypeProperty(
							K::TYPE_FLAGS) |
						K::TYPE_FLAG_MANDATORY_LENGTH
					]),
				'numeric' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'NUMERIC',
						K::TYPE_DATA_TYPE => K::DATATYPE_NUMBER,
						K::TYPE_FLAGS => TypeHelper::getDefaultTypeProperty(
							K::TYPE_FLAGS) | K::TYPE_FLAG_FRACTION_SCALE |
						K::TYPE_FLAG_LENGTH
					]),
				'integer' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'INTEGER',
						K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER
					]),
				'smallint' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'SMALLINT',
						K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
						K::TYPE_SIZE => 16
					]),
				'bigint' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'BIGINT',
						K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER,
						K::TYPE_SIZE => 64
					]),
				'real' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'REAL',
						K::TYPE_DATA_TYPE => K::DATATYPE_FLOAT
					]),
				'date' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'DATE',
						K::TYPE_DATA_TYPE => K::DATATYPE_DATE
					]),
				'time' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'TIME',
						K::TYPE_DATA_TYPE => K::DATATYPE_TIME
					]),
				'timestamp' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'TIMESTAMP',
						K::TYPE_DATA_TYPE => K::DATATYPE_DATETIME
					])
			]);

		$this->offsetSet('decimal', $this->offsetGet('numeric'));
		$this->offsetSet('float', $this->offsetGet('real'));
		$this->offsetSet('double precision', $this->offsetGet('real'));
	}
}
