<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\DateTime;
use NoreSources\Container\Container;
use NoreSources\Expression\Value;
use NoreSources\SQL\DBMS\AbstractPlatform;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\ConnectionProviderInterface;
use NoreSources\SQL\DBMS\TimestampFormatTranslationMap;
use NoreSources\SQL\DBMS\Filesystem\ClosureStructureFilenameFactory;
use NoreSources\SQL\DBMS\Filesystem\StructureFilenameFactoryInterface;
use NoreSources\SQL\DBMS\Filesystem\StructureFilenameFactoryProviderInterface;
use NoreSources\SQL\DBMS\SQLite\SQLiteConstants as K;
use NoreSources\SQL\DBMS\Traits\ConnectionProviderTrait;
use NoreSources\SQL\Syntax\FunctionCall;
use NoreSources\SQL\Syntax\MetaFunctionCall;
use NoreSources\SQL\Syntax\TableConstraintDeclaration;
use NoreSources\SQL\Syntax\Statement\ParameterData;
use NoreSources\SQL\Syntax\Statement\Structure\CreateIndexQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateNamespaceQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery;
use NoreSources\SQL\Syntax\Statement\Structure\DropNamespaceQuery;
use NoreSources\Text\Text;
use NoreSources\Type\TypeConversion;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class SQLitePlatform extends AbstractPlatform implements
	StructureFilenameFactoryProviderInterface,
	ConnectionProviderInterface
{
	use LoggerAwareTrait;
	use ConnectionProviderTrait;

	const DEFAULT_VERSION = '3.0.0';

	public function __construct($parameters,
		ConnectionInterface $connection)
	{
		parent::__construct($parameters);
		$this->setConnection($connection);

		if (($f = Container::keyValue($parameters,
			self::STRUCTURE_FILENAME_FACTORY)))
		{
			if (!($f instanceof StructureFilenameFactoryInterface))
				$f = new ClosureStructureFilenameFactory($f);
			$this->filenameFactory = $f;
		}

		$this->initializeStatementFactory(
			[
				K::QUERY_CREATE_TABLE => SQLiteCreateTableQuery::class,
				CreateTableQuery::class => SQLiteCreateTableQuery::class,
				K::QUERY_CREATE_NAMESPACE => SQLiteCreateNamespaceQuery::class,
				CreateNamespaceQuery::class => SQLiteCreateNamespaceQuery::class,
				K::QUERY_CREATE_INDEX => SQLiteCreateIndexQuery::class,
				CreateIndexQuery::class => SQLiteCreateIndexQuery::class,
				K::QUERY_DROP_NAMESPACE => SQLiteDropNamespaceQuery::class,
				DropNamespaceQuery::class => SQLiteDropNamespaceQuery::class
				// RenameColumnQuery::class => RenameColumnQuery::class
			]);

		$this->setPlatformFeature(K::FEATURE_NAMED_PARAMETERS, true);

		$this->setPlatformFeature(
			[
				K::FEATURE_INSERT,
				K::FEATURE_INSERT_FLAGS
			],
			(K::FEATURE_INSERT_FLAG_SELECT |
			K::FEATURE_INSERT_FLAG_DEFAULTVALUES));

		$this->setPlatformFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_CREATE_FLAGS
			],
			(K::FEATURE_CREATE_TEMPORARY |
			K::FEATURE_CREATE_EXISTS_CONDITION));

		$this->setPlatformFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_ELEMENT_TABLE,
				K::FEATURE_COLUMN_DECLARATION_FLAGS
			], (K::FEATURE_COLUMN_SIGNNESS_TYPE_PREFIX));

		foreach ([
			K::FEATURE_ELEMENT_TABLE,
			K::FEATURE_ELEMENT_VIEW,
			K::FEATURE_ELEMENT_INDEX
		] as $type)
		{
			$this->setPlatformFeature(
				[
					K::FEATURE_DROP,
					$type,
					K::FEATURE_DROP_FLAGS
				], (K::FEATURE_DROP_EXISTS_CONDITION));
		}
	}

	public function serializeTimestamp($value, $dataType)
	{
		$value = TypeConversion::toDateTime($value);
		if (($dataType & K::DATATYPE_TIMEZONE) != K::DATATYPE_TIMEZONE)
			$value->setTimezone(DateTime::getUTCTimezone());
		return $this->quoteStringValue(
			$value->format(
				$this->getTimestampTypeStringFormat($dataType)));
	}

	public function getColumnType($columnDescription,
		$constraintFlags = 0)
	{
		$registry = $this->getTypeRegistry();
		$type = Container::firstValue(
			$registry->matchDescription($columnDescription));
		if (!$type)
			return $registry->get('text');

		return $type;
	}

	public function getParameter($name,
		$valueDataType = K::DATATYPE_UNDEFINED,
		ParameterData $parameters = null)
	{
		return ':' . $name;
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
				return 'PRIMARY KEY AUTOINCREMENT';
		}

		return parent::getKeyword($keyword);
	}

	/**
	 *
	 * Mostly the same as the default behavior except for timezone
	 * where HH:mm is preferred to ISO HHmm to be more friendly with
	 * SQLite date & time functions
	 *
	 * @see https://www.sqlite.org/lang_datefunc.html
	 */
	public function getTimestampTypeStringFormat($type = 0)
	{
		$timezoneFormat = (version_compare(PHP_VERSION, '8.0.0') >= 0) ? 'p' : 'P';

		if ($type == K::DATATYPE_TIMESTAMP)
			return 'Y-m-d H:i:s' . $timezoneFormat;
		elseif ($type == (K::DATATYPE_TIME | K::DATATYPE_TIMEZONE))
			return 'H:i:s' . $timezoneFormat;
		return parent::getTimestampTypeStringFormat($type);
	}

	public function getTimestampFormatTokenTranslation($formatToken)
	{
		if (!Container::isArray(self::$timestampFormatTranslations))
		{
			self::$timestampFormatTranslations = new TimestampFormatTranslationMap(
				[
					DateTime::FORMAT_YEAR_NUMBER => '%Y',
					DateTime::FORMAT_YEAR_DIGIT_2 => [
						'%Y',
						'Two digits year number format is not available'
					],
					DateTime::FORMAT_YEAR_ISO8601 => [
						'%Y',
						'Not the ISO 8601 week numbering year'
					],

					DateTime::FORMAT_MONTH_DIGIT_2 => '%m',
					DateTime::FORMAT_MONTH_NUMBER => [
						'%m',
						'Month number without leading zero is not available'
					],
					DateTime::FORMAT_WEEK_DIGIT_2 => [
						'%W',
						'Not ISO-8601. Week number may differ'
					],
					DateTime::FORMAT_DAY_DIGIT_2 => '%d',
					DateTime::FORMAT_DAY_NUMBER => [
						'%d',
						'Leading zero cannot be omited'
					],
					DateTime::FORMAT_YEAR_DAY_NUMBER => [
						'%j',
						'Day of year range will be [1-366] instead of [0-365]'
					],
					DateTime::FORMAT_WEEK_DAY_ISO8601 => [
						'%w',
						'Week day "sunday" will be 0 instead of 7'
					],
					DateTime::FORMAT_WEEK_DAY_NUMBER => '%w',
					DateTime::FORMAT_HOUR_24_DIGIT_2 => '%H', // it's 00-24 insteand of 23 but it's
					                                           // 99% ok. No need to notice
					DateTime::FORMAT_HOUR_24_PADDED => [
						'%H',
						'24-Hour without leading zero is not available'
					],
					DateTime::FORMAT_MINUTE_DIGIT_2 => '%M',
					DateTime::FORMAT_SECOND_DIGIT_2 => '%S',
					DateTime::FORMAT_EPOCH_OFFSET => '%s',
					DateTime::FORMAT_TIMESTAMP_ISO8601 => [
						'%Y-%m-%dT%H:%M:%SZ',
						'Not the ISO 8601 week numbering year. No timezone'
					]
				]);
		}

		return Container::keyValue(self::$timestampFormatTranslations,
			$formatToken, null);
	}

	public function newConfigurator(ConnectionInterface $connection)
	{
		return new SQLiteConfigurator($this, $connection);
	}

	public function newExpression($baseClassname, ...$arguments)
	{
		switch ($baseClassname)
		{
			case TableConstraintDeclaration::class:
				return new SQLiteTableConstraintDeclaration(
					...$arguments);
		}
		return parent::newExpression($baseClassname, ...$arguments);
	}

	public function getStructureFilenameFactory()
	{
		return $this->filenameFactory;
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

	public function quoteStringValue($value)
	{
		return "'" . \SQLite3::escapeString($value) . "'";
	}

	public function quoteBinaryData($value)
	{
		return "X'" . Text::toHexadecimalString($value) . "'";
	}

	public function quoteIdentifier($identifier)
	{
		return '"' . $identifier . '"';
	}

	public function getTypeRegistry()
	{
		return SQLite3TypeRegistry::getInstance();
	}

	private function translateTimestampFormatFunction(
		MetaFunctionCall $metaFunction)
	{
		$format = $metaFunction->getArgument(0);
		if ($format instanceof Value)
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

				$t = $this->getTimestampFormatTokenTranslation($c);

				if ($t === null)
					$t = $c;
				elseif ($t === false)
				{
					if ($this->logger instanceof LoggerInterface)
						$this->logger->warning(
							'Timestamp format "' . $c .
							'" is nut supported by SQLite strftime');
					continue;
				}
				elseif (\is_array($t))
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
	 * @var TimestampFormatTranslationMap
	 */
	private static $timestampFormatTranslations;

	/**
	 *
	 * @var StructureFilenameFactoryInterface
	 */
	private $filenameFactory;
}
