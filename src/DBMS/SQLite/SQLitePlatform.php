<?php
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\Container;
use NoreSources\DateTime;
use NoreSources\Text;
use NoreSources\MediaType\MediaType;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\AbstractPlatform;
use NoreSources\SQL\DBMS\TimestampFormatTranslationMap;
use NoreSources\SQL\DBMS\TypeRegistry;
use NoreSources\SQL\DBMS\Types\ArrayObjectType;
use NoreSources\SQL\Expression\ColumnDeclaration;
use NoreSources\SQL\Expression\FunctionCall;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Expression\MetaFunctionCall;
use NoreSources\SQL\Expression\TableConstraintDeclaration;
use NoreSources\SQL\Statement\ParameterData;
use NoreSources\SQL\Structure\ColumnDescriptionInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class SQLitePlatform extends AbstractPlatform
{
	use LoggerAwareTrait;

	const DEFAULT_VERSION = '3.0.0';

	public function __construct($parameters = array())
	{
		parent::__construct($parameters);

		$this->initializeStatementFactory(
			[
				K::QUERY_CREATE_TABLE => SQLiteCreateTableQuery::class,
				K::QUERY_CREATE_NAMESPACE => SQLiteCreateNamespaceQuery::class
			]);

		$this->setPlatformFeature(
			[
				self::FEATURE_INSERT,
				self::FEATURE_DEFAULT
			], false);
		$this->setPlatformFeature(
			[
				self::FEATURE_INSERT,
				self::FEATURE_DEFAULTVALUES
			], true);
		$this->setPlatformFeature(
			[
				self::FEATURE_CREATE,
				self::FEATURE_TEMPORARY
			], true);
		$this->setPlatformFeature(
			[
				self::FEATURE_CREATE,
				self::FEATURE_EXISTS_CONDITION
			], true);
		$this->setPlatformFeature(
			[
				self::FEATURE_SELECT,
				self::FEATURE_EXTENDED_RESULTCOLUMN_RESOLUTION
			], true);
		$this->setPlatformFeature([
			self::FEATURE_SCOPED
		], true);
	}

	public function getColumnType(ColumnDescriptionInterface $column,
		$constraintFlags = 0)
	{
		$registry = $this->getTypeRegistry();
		$type = Container::firstValue(
			$registry->matchDescription($column));
		if (!$type)
			return $registry->get('text');

		return $type;
	}

	public function getParameter($name, ParameterData $parameters = null)
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
		if ($type == K::DATATYPE_TIMESTAMP)
			return 'Y-m-d H:i:sP';
		elseif ($type == (K::DATATYPE_TIME | K::DATATYPE_TIMEZONE))
			return 'H:i:sP';
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
						'%Y-%m-%dT%H:%M:%S+00:00',
						'Not the ISO 8601 week numbering year. No timezone'
					]
				]);
		}

		return Container::keyValue(self::$timestampFormatTranslations,
			$formatToken, null);
	}

	public function newExpression($baseClassname, ...$arguments)
	{
		switch ($baseClassname)
		{
			case ColumnDeclaration::class:
				return new SQLiteColumnDeclaration(...$arguments);
			case TableConstraintDeclaration::class:
				return new SQLiteTableConstraintDeclaration(
					...$arguments);
		}
		return parent::newExpression($baseClassname, ...$arguments);
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
		if (!isset(self::$typeRegistry))
		{
			self::$typeRegistry = new TypeRegistry(
				[
					'blob' => new ArrayObjectType(
						[
							K::TYPE_NAME => 'BLOB',
							K::TYPE_DATA_TYPE => K::DATATYPE_BINARY
						]),
					'integer' => new ArrayObjectType(
						[
							K::TYPE_NAME => 'INTEGER',
							K::TYPE_DATA_TYPE => K::DATATYPE_INTEGER |
							K::DATATYPE_BOOLEAN
						]),
					'real' => new ArrayObjectType(
						[
							K::TYPE_NAME => 'REAL',
							K::TYPE_DATA_TYPE => K::DATATYPE_FLOAT,
							K::TYPE_FLAGS => K::TYPE_FLAG_FRACTION_SCALE
						]),
					'text' => new ArrayObjectType(
						[
							K::TYPE_NAME => 'TEXT',
							K::TYPE_DATA_TYPE => K::DATATYPE_TIMESTAMP |
							K::DATATYPE_STRING
						]),
					'json' => new ArrayObjectType(
						[
							K::TYPE_NAME => 'JSON',
							K::TYPE_DATA_TYPE => K::DATATYPE_STRING,
							K::TYPE_MEDIA_TYPE => MediaType::fromString(
								'application/json')
						])
				]);
		}

		return self::$typeRegistry;
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
	 * @var TypeRegistry
	 */
	private static $typeRegistry;
}
