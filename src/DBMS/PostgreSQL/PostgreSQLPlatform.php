<?php
namespace NoreSources\SQL\DBMS\PostgreSQL;

use NoreSources\Container;
use NoreSources\DateTime;
use NoreSources\SemanticVersion;
use NoreSources\SQL\DBMS\AbstractPlatform;
use NoreSources\SQL\DBMS\TimestampFormatTranslationMap;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConstants as K;
use NoreSources\SQL\Expression\FunctionCall;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Expression\MetaFunctionCall;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class PostgreSQLPlatform extends AbstractPlatform
{
	use LoggerAwareTrait;

	/**
	 *
	 * @var string
	 */
	const DEFAULT_VERSION = '7.3.0';

	public function __construct($version = self::DEFAULT_VERSION)
	{
		parent::__construct($version);

		$this->setPlatformFeature(
			[
				self::FEATURE_INSERT,
				self::FEATURE_DEFAULT
			], true);
		$this->setPlatformFeature(
			[
				self::FEATURE_INSERT,
				self::FEATURE_DEFAULTVALUES
			], true);

		$serverVersion = $this->getPlatformVersion();
		$compatibility = $serverVersion->slice(SemanticVersion::MAJOR,
			SemanticVersion::MAJOR);

		if (SemanticVersion::compareVersions($serverVersion, '7.3.0') >=
			0)
		{
			$this->setPlatformFeature(
				[
					self::FEATURE_DROP,
					self::FEATURE_CASCADE
				], false);

			$compatibility = '7.3.0';
		}

		if (SemanticVersion::compareVersions($serverVersion, '8.2.0') >=
			0)
		{
			$this->setPlatformFeature(
				[
					self::FEATURE_DROP,
					self::FEATURE_EXISTS_CONDITION
				], true);

			$compatibility = '8.2.0';
		}

		if (SemanticVersion::compareVersions($serverVersion, '9.1.0') >=
			0)
		{
			$this->setPlatformFeature(
				[
					self::FEATURE_CREATE,
					self::FEATURE_TABLE,
					self::FEATURE_EXISTS_CONDITION
				], true);

			$compatibility = '9.1.0';
		}

		if (SemanticVersion::compareVersions($serverVersion, '9.3.0') >=
			0)
		{
			$this->setPlatformFeature(
				[
					self::FEATURE_CREATE,
					self::FEATURE_NAMESPACE,
					self::FEATURE_EXISTS_CONDITION
				], true);

			$compatibility = '9.3.0';
		}

		if (SemanticVersion::compareVersions($serverVersion, '10.0.0') >=
			0)
		{
			$compatibility = '10.0.0';
		}

		$compatibility = ($compatibility instanceof SemanticVersion) ? $compatibility : new SemanticVersion(
			$compatibility);
		$this->setPlatformVersion(self::VERSION_COMPATIBILITY,
			$compatibility);
	}

	public function getKeyword($keyword)
	{
		switch ($keyword)
		{
			case K::KEYWORD_NAMESPACE:
				return 'SCHEMA';
			case K::KEYWORD_AUTOINCREMENT:
				return '';
		}
		return parent::getKeyword($keyword);
	}

	public function getTimestampFormatTokenTranslation($formatToken)
	{
		if (!Container::isArray(self::$timestampFormatTranslations))
		{
			self::$timestampFormatTranslations = new TimestampFormatTranslationMap(
				[
					DateTime::FORMAT_YEAR_NUMBER => 'YYYY',
					DateTime::FORMAT_YEAR_DIGIT_2 => 'YY',
					DateTime::FORMAT_YEAR_ISO8601 => 'IYYY',
					DateTime::FORMAT_MONTH_ALPHA_3 => 'Mon',
					DateTime::FORMAT_MONTH_NAME => 'FMMonth',
					DateTime::FORMAT_MONTH_DIGIT_2 => 'MM',
					DateTime::FORMAT_MONTH_NUMBER => 'FMMM',
					DateTime::FORMAT_WEEK_DIGIT_2 => 'IW',
					DateTime::FORMAT_DAY_NAME => 'FMDay',
					DateTime::FORMAT_DAY_ALPHA_3 => 'Dy',
					DateTime::FORMAT_DAY_DIGIT_2 => 'DD',
					DateTime::FORMAT_DAY_NUMBER => 'FMDD',
					DateTime::FORMAT_YEAR_DAY_NUMBER => [
						'DDD',
						'Day of year range will be [1-366] instead of [0-365]'
					],
					DateTime::FORMAT_WEEK_DAY_ISO8601 => 'ID',
					DateTime::FORMAT_WEEK_DAY_NUMBER => [
						'D',
						'Day of week range will be [1-7] instead of [0-6]'
					],
					// Hours
					DateTime::FORMAT_HOUR_24_DIGIT_2 => 'HH24',
					DateTime::FORMAT_HOUR_24_PADDED => 'FMHH24',
					DateTime::FORMAT_HOUR_12_DIGIT_2 => 'HH',
					DateTime::FORMAT_HOUR_12_PADDED => 'FMHH',
					DateTime::FORMAT_HOUR_AM_UPPERCASE => 'AM',
					DateTime::FORMAT_HOUR_AM_LOWERCASE => 'am',
					DateTime::FORMAT_MINUTE_DIGIT_2 => 'MI',
					DateTime::FORMAT_SECOND_DIGIT_2 => 'SS',
					DateTime::FORMAT_MILLISECOND => 'MS',
					DateTime::FORMAT_MICROSECOND => 'US',
					// Time zone
					DateTime::FORMAT_TIMEZONE_GMT_OFFSET_COLON => [
						'OF',
						'Minute offset will not be included'
					],
					DateTime::FORMAT_TIMEZONE_ALPHA_3 => [
						'TZ',
						'Timezone abbreviations may differ and are not available on timestamp without timezone'
					],
					DateTime::FORMAT_TIMESTAMP_ISO8601 => [
						'YYY-MM-DD"T"HH24:MI:SSOF',
						'Time zone offset will contain colon(s)'
					]
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
			return $this->translateTimestampFunction($metaFunction);
		}

		return parent::translateFunction($metaFunction);
	}

	private function translateTimestampFunction(
		MetaFunctionCall $metaFunction)
	{
		$format = $metaFunction->getArgument(0);
		if ($format instanceof Literal)
		{
			$s = \str_split(\strval($format->getValue()));
			$escapeChar = '\\';
			$translation = '';
			$escape = 0;
			$quoted = false;
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
					if (!$quoted)
						$translation .= '"';

					$escape = 0;
					$translation .= $c;

					if (!$quoted)
						$translation .= '"';

					continue;
				}

				$t = $c;
				if (Container::keyExists(
					DateTime::getFormatTokenDescriptions(), $c))
				{
					$t = $this->getTimestampFormatTokenTranslation($c);

					if ($quoted)
						$translation .= '"';

					$quoted = false;

					if ($t === false)
					{
						if ($this->logger instanceof LoggerInterface)
							$this->logger->warning(
								'Timestamp format "' . $c .
								'" nut supported by PostgreSQL to_char');
						continue;
					}

					if (\is_array($t))
					{
						if ($this->logger instanceof LoggerInterface)
							$this->logger->notice(
								'Timestamp format "' . $c . '": ' . $t[1]);
						$t = $t[0];
					}
				}
				else
				{
					if (!$quoted)
						$translation .= '"';

					$quoted = true;
				}

				$translation .= $t;
			}

			if ($quoted)
				$translation .= '"';

			$format->setValue($translation);
		}

		$timestamp = $metaFunction->getArgument(1);
		$to_char = new FunctionCall('to_char', [
			$timestamp,
			$format
		]);

		return $to_char;
	}

	/**
	 *
	 * @var TimestampFormatTranslationMap
	 *
	 */
	private static $timestampFormatTranslations;
}
