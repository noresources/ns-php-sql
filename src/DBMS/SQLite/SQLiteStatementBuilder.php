<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\Container;
use NoreSources\Text;
use NoreSources\SQL\DBMS\PlatformInterface;
use NoreSources\SQL\DBMS\PlatformProviderTrait;
use NoreSources\SQL\DBMS\SQLite\SQLiteConstants as K;
use NoreSources\SQL\Statement\AbstractStatementBuilder;
use NoreSources\SQL\Statement\ClassMapStatementFactoryTrait;
use NoreSources\SQL\Statement\ParameterData;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\StructureElementInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class SQLiteStatementBuilder extends AbstractStatementBuilder implements
	LoggerAwareInterface
{
	use LoggerAwareTrait;
	use ClassMapStatementFactoryTrait;
	use PlatformProviderTrait;

	public function __construct(PlatformInterface $platform)
	{
		parent::__construct();
		$this->platform = $platform;

		$this->initializeStatementFactory(
			[
				K::QUERY_CREATE_TABLE => SQLiteCreateTableQuery::class,
				K::QUERY_CREATE_NAMESPACE => SQLiteCreateNamespaceQuery::class
			]);

		$this->sqliteSettings = new \ArrayObject();
	}

	public function getSQLiteSetting($key, $dflt = null)
	{
		return Container::keyValue($this->sqliteSettings, $key, $dflt);
	}

	public function setSQLiteSettings($array)
	{
		$dflts = [
			K::CONNECTION_DATABASE_FILE_PROVIDER => [
				static::class,
				'buildSQLiteFilePath'
			]
		];

		$this->sqliteSettings->exchangeArray(
			\array_merge($dflts, $array));
	}

	public static function buildSQLiteFilePath(
		StructureElementInterface $structure)
	{
		$path = $structure->getName() . '.sqlite';
		while ($structure->getParentElement())
		{
			$structure = $structure->getParentElement();
			$directory = $structure->getName();
			if (\strlen($directory))
				$path = $directory . '/' . $path;
		}

		return $path;
	}

	public function serializeString($value)
	{
		return "'" . \SQLite3::escapeString($value) . "'";
	}

	public function serializeBinary($value)
	{
		return "X'" . Text::toHexadecimalString($value) . "'";
	}

	public function escapeIdentifier($identifier)
	{
		return '"' . $identifier . '"';
	}

	public function getParameter($name, ParameterData $parameters = null)
	{
		return (':' . preg_replace('/[^a-zA-Z0-9_]/', '_', $name));
	}

	public static function getSQLiteColumnTypeName(
		ColumnStructure $column)
	{
		$dataType = K::DATATYPE_UNDEFINED;
		if ($column->hasColumnProperty(K::COLUMN_DATA_TYPE))
			$dataType = $column->getColumnProperty(K::COLUMN_DATA_TYPE);

		switch ($dataType)
		{
			case K::DATATYPE_BINARY:
				return 'BLOB';
			case K::DATATYPE_NUMBER:
			case K::DATATYPE_FLOAT:
				return 'REAL';
			case K::DATATYPE_BOOLEAN:
			case K::DATATYPE_INTEGER:
				return 'INTEGER';
			case K::DATATYPE_NULL:
				return NULL;
		}

		return 'TEXT';
	}
}