<?php
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\Container;
use NoreSources\DateTime;
use NoreSources\SQL\DBMS\AbstractPlatform;
use NoreSources\SQL\DBMS\ArrayObjectType;
use NoreSources\SQL\DBMS\BasicType;
use NoreSources\SQL\DBMS\TypeHelper;
use NoreSources\SQL\DBMS\TypeInterface;
use NoreSources\SQL\DBMS\MySQL\MySQLConstants as K;
use NoreSources\SQL\Expression\FunctionCall;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Expression\MetaFunctionCall;
use NoreSources\SQL\Structure\ColumnDescriptionInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class MySQLPlatform extends AbstractPlatform
{
	use LoggerAwareTrait;

	const DEFAULT_VERSION = '4.0.0';

	public function __construct($version)
	{
		parent::__construct($version);
		$this->setPlatformFeature(
			[
				self::FEATURE_INSERT,
				self::FEATURE_DEFAULT
			], true);

		$this->setPlatformFeature(
			[
				self::FEATURE_CREATE,
				self::FEATURE_TABLE,
				self::FEATURE_REPLACE
			], true);
		$this->setPlatformFeature(
			[
				self::FEATURE_CREATE,
				self::FEATURE_TABLE,
				self::FEATURE_TEMPORARY
			], true);

		$this->setPlatformFeature(
			[
				self::FEATURE_CREATE,
				self::FEATURE_TABLE,
				self::FEATURE_COLUMN_DECLARATION_FLAGS
			],
			(self::FEATURE_COLUMN_ENUM |
			self::FEATURE_COLUMN_KEY_MANDATORY_LENGTH));
	}

	public function getColumnType(ColumnDescriptionInterface $column,
		$constraintFlags = 0)
	{
		if ($column->hasColumnProperty(K::COLUMN_ENUMERATION))
		{
			return new BasicType('ENUM');
		}

		$types = MySQLType::getMySQLTypes();
		$table = $column->getParentElement();
		$isPrimaryKey = (($constraintFlags &
			K::COLUMN_CONSTRAINT_PRIMARY_KEY) ==
			K::COLUMN_CONSTRAINT_PRIMARY_KEY);

		if ($isPrimaryKey)
		{
			// Types must have a key length
			$types = Container::filter($types,
				function ($_, $type) {
					/**
					 *
					 * @var TypeInterface $type
					 */

					if ((TypeHelper::getProperty($type, K::TYPE_FLAGS) &
					K::TYPE_FLAG_LENGTH) == K::TYPE_FLAG_LENGTH)
					{
						$maxLength = TypeHelper::getMaxLength($type);
						return !\is_infinite($maxLength);
					}
					return false;
				});
		}

		$types = TypeHelper::getMatchingTypes($column, $types);

		if (Container::count($types) > 0)
		{
			list ($key, $type) = Container::first($types);
			/**
			 *
			 * @var ArrayObjectType $type
			 */

			if ($isPrimaryKey)
			{

				/**
				 * Use active character set maxlen instead
				 *
				 * @var integer $glyphLength
				 */
				$glyphLength = 4;
				$keyMaxLength = intval(
					floor(K::KEY_MAX_LENGTH / $glyphLength));
				$typeMaxLength = TypeHelper::getMaxLength($type);

				if ($typeMaxLength > $keyMaxLength)
				{
					$type = new ArrayObjectType(
						\array_merge($type->getArrayCopy(),
							[
								K::TYPE_MAX_LENGTH => $keyMaxLength
							]));
				}
			}

			return $type;
		}

		return new BasicType('TEXT');
	}

	public function getKeyword($keyword)
	{
		switch ($keyword)
		{
			case K::KEYWORD_NAMESPACE:
				return 'DATABASE';
			case K::KEYWORD_AUTOINCREMENT:
				return 'AUTO_INCREMENT';
		}
		return parent::getKeyword($keyword);
	}

	public function getTimestampTypeStringFormat($type = 0)
	{
		if ($type == K::DATATYPE_TIMESTAMP)
			return 'Y-m-d H:i:s';
		elseif ($type == (K::DATATYPE_TIME | K::DATATYPE_TIMEZONE))
			return 'H:i:s';

		return parent::getTimestampTypeStringFormat($type);
	}

	public function getTimestampFormatTokenTranslation($formatToken)
	{
		if (!Container::isArray(self::$timestampFormatTranslations))
		{
			self::$timestampFormatTranslations = new \ArrayObject(
				[
					// YEAR
					DateTime::FORMAT_YEAR_NUMBER => '%Y',
					DateTime::FORMAT_YEAR_DIGIT_2 => '%y',
					DateTime::FORMAT_YEAR_ISO8601 => [
						'%Y',
						'Standard year number'
					],
					DateTime::FORMAT_MONTH_ALPHA_3 => '%b',
					DateTime::FORMAT_MONTH_NAME => '%M',
					DateTime::FORMAT_MONTH_DIGIT_2 => '%m',
					DateTime::FORMAT_MONTH_NUMBER => '%c',
					DateTime::FORMAT_WEEK_DIGIT_2 => '%v',
					// A full textual representation of the day
					DateTime::FORMAT_DAY_NAME => '%W',
					DateTime::FORMAT_DAY_ALPHA_3 => '%a',
					DateTime::FORMAT_DAY_DIGIT_2 => '%d',
					DateTime::FORMAT_DAY_NUMBER => '%e',
					DateTime::FORMAT_YEAR_DAY_NUMBER => [
						'%j',
						'Day of year range will be [1-366] instead of [0-365]'
					],
					DateTime::FORMAT_WEEK_DAY_ISO8601 => [
						'%w',
						'Week day "sunday" will be 0 instead of 7'
					],
					DateTime::FORMAT_WEEK_DAY_EN_ALPHA_2 => false,
					DateTime::FORMAT_WEEK_DAY_NUMBER => '%w',
					DateTime::FORMAT_HOUR_24_DIGIT_2 => '%H',
					DateTime::FORMAT_HOUR_24_PADDED => '%k',
					DateTime::FORMAT_HOUR_12_DIGIT_2 => '%h',
					DateTime::FORMAT_HOUR_12_PADDED => '%l',
					DateTime::FORMAT_HOUR_AM_UPPERCASE => '%p',
					DateTime::FORMAT_HOUR_AM_LOWERCASE => false,
					DateTime::FORMAT_MINUTE_DIGIT_2 => '%i',
					DateTime::FORMAT_SECOND_DIGIT_2 => '%S',
					DateTime::FORMAT_MICROSECOND => false,
					DateTime::FORMAT_MICROSECOND => '%f'
				]);
		}

		return Container::keyValue(self::$timestampFormatTranslations,
			$formatToken, null);
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
							'" not supported by MySQL date_format()');
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
		$strftime = new FunctionCall('date_format',
			[
				$timestamp,
				$format
			]);

		return $strftime;
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private static $timestampFormatTranslations;
}