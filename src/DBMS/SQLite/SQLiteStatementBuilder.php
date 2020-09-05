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
use NoreSources\SQL\DBMS\BasicType;
use NoreSources\SQL\DBMS\SQLite\SQLiteConstants as K;
use NoreSources\SQL\Expression\FunctionCall;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Expression\MetaFunctionCall;
use NoreSources\SQL\Statement\ParameterData;
use NoreSources\SQL\Statement\StatementBuilder;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\StructureElementInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use ArrayObject;

class SQLiteStatementBuilder extends StatementBuilder implements
	LoggerAwareInterface
{
	use LoggerAwareTrait;

	public function __construct()
	{
		parent::__construct();

		$this->sqliteSettings = new \ArrayObject();

		$this->setBuilderFlags(K::BUILDER_DOMAIN_GENERIC,
			K::BUILDER_IF_EXISTS | K::BUILDER_IF_NOT_EXISTS |
			K::BUILDER_SCOPED_STRUCTURE_DECLARATION);
		$this->setBuilderFlags(K::BUILDER_DOMAIN_SELECT,
			K::BUILDER_SELECT_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION);

		$this->setBuilderFlags(K::BUILDER_DOMAIN_CREATE_TABLE,
			K::BUILDER_CREATE_TEMPORARY);

		$this->setBuilderFlags(K::BUILDER_DOMAIN_INSERT,
			K::BUILDER_INSERT_DEFAULT_VALUES);
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

	public function translateFunction(MetaFunctionCall $metaFunction)
	{
		if ($metaFunction->getFunctionName() ==
			K::METAFUNCTION_TIMESTAMP_FORMAT)
		{
			return $this->translateTimestampFormatFunction(
				$metaFunction);
		}

		return parent::translateFunction($metaFunction);
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

	public function getColumnType(ColumnStructure $column)
	{
		return new BasicType($this->getSQLiteColumnTypeName($column));
	}

	public function getKeyword($keyword)
	{
		switch ($keyword)
		{
			case K::KEYWORD_TRUE:
				return 1;
			case K::KEYWORD_FALSE:
				return 0;
			case K::KEYWORD_AUTOINCREMENT:
				return 'AUTOINCREMENT';
		}

		return parent::getKeyword($keyword);
	}

	/**
	 * Mostly the same as the default behavior except for timezone
	 * where HH:mm is preferred to ISO HHmm to be more friendly with
	 * SQLite date & time functions
	 *
	 * @see https://www.sqlite.org/lang_datefunc.html
	 */
	public function getTimestampFormat($type = 0)
	{
		if ($type == K::DATATYPE_TIMESTAMP)
			return 'Y-m-d H:i:sP';
		elseif ($type == (K::DATATYPE_TIME | K::DATATYPE_TIMEZONE))
			return 'H:i:sP';
		return parent::getTimestampFormat($type);
	}

	/**
	 *
	 * @return ArrayObject
	 */
	public static function getTimestampFormatTranslations()
	{
		if (!Container::isArray(self::$timestampFormatTranslations))
		{
			self::$timestampFormatTranslations = new \ArrayObject(
				[
					'Y' => '%Y',
					'y' => [
						'%Y',
						'Two digits year number format is not available'
					],
					/**
					 *
					 * @todo uncler. May differ
					 */
					'o' => [
						'%Y',
						'Not the ISO 8601 week numbering year'
					],

					'L' => false,
					'M' => false,
					'F' => false,
					'm' => '%m',
					'n' => [
						'%m',
						'Month number without leading zero is not available'
					],
					'W' => [
						'%W',
						'Not ISO-8601. Week number may differ'
					],
					'l' => false,
					't' => false,
					'D' => false,
					'd' => '%d',
					'j' => [
						'%d',
						'Leading zero cannot be omited'
					],
					'z' => [
						'%j',
						'Day of year range will be [1-366] instead of [0-365]'
					],
					'N' => [
						'%w',
						'Week day "sunday" will be 0 instead of 7'
					],
					'S' => false,
					'w' => '%w',
					'H' => '%H', // it's 00-24 insteand of 23 but it's 99% ok. No need to notice
					'G' => [
						'%H',
						'24-Hour without leading zero is not available'
					],
					'h' => false,
					'g' => false,
					'B' => false,
					'A' => false,
					'a' => false,
					'i' => '%M',
					's' => '%S',
					'v' => false,
					'u' => false,
					'Z' => false,
					'O' => false,
					'P' => false,
					'e' => false,
					'T' => false,
					'I' => false,
					'r' => false,
					'c' => false,
					'U' => '%s'
				]);
		}

		return self::$timestampFormatTranslations;
	}

	private function translateTimestampFormatFunction(
		MetaFunctionCall $metaFunction)
	{
		$format = $metaFunction->getArgument(0);
		if ($format instanceof Literal)
		{
			$s = \str_split(\strval($format->getValue()));
			$escapeChar = '\\';
			$translation = '';
			$escape = 0;
			foreach ($s as $c)
			{
				if ($c == $escapeChar)
				{
					$escape++;
					if ($escape == 2)
					{
						$translation .= $escapeChar;
						$escape = 0;
					}

					continue;
				}

				if ($escape)
				{
					$escape = 0;
					$translation .= $c;
					continue;
				}

				$t = Container::keyValue(
					self::getTimestampFormatTranslations(), $c, $c);

				if ($t === false)
				{
					if ($this->logger instanceof LoggerInterface)
						$this->logger->warning(
							'Timestamp format "' . $c .
							'" is nut supported by SQLite strftime');
					continue;
				}

				if (\is_array($t))
				{
					if ($this->logger instanceof LoggerInterface)
						$this->logger->notice(
							'Timestamp format "' . $c . '": ' . $t[1]);
					$t = $t[0];
				}

				$translation .= $t;
			}

			$format->setValue($translation);
		}

		$timestamp = $metaFunction->getArgument(1);
		$strftime = new FunctionCall('strftime', [
			$format,
			$timestamp
		]);

		return $strftime;
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private static $timestampFormatTranslations;

	/**
	 *
	 * @var \ArrayObject
	 */
	private $sqliteSettings;
}