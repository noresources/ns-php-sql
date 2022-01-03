<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\PostgreSQL;

use NoreSources\DateTime;
use NoreSources\SemanticVersion;
use NoreSources\Container\Container;
use NoreSources\Expression\Value;
use NoreSources\SQL\DBMS\AbstractPlatform;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\ConnectionProviderInterface;
use NoreSources\SQL\DBMS\TimestampFormatTranslationMap;
use NoreSources\SQL\DBMS\TypeRegistry;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConstants as K;
use NoreSources\SQL\DBMS\Traits\ConnectionProviderTrait;
use NoreSources\SQL\DBMS\Types\BasicType;
use NoreSources\SQL\Syntax\FunctionCall;
use NoreSources\SQL\Syntax\MetaFunctionCall;
use NoreSources\SQL\Syntax\Statement\ParameterData;
use NoreSources\Type\TypeConversion;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class PostgreSQLPlatform extends AbstractPlatform implements
	ConnectionProviderInterface
{
	use LoggerAwareTrait;
	use ConnectionProviderTrait;

	/**
	 *
	 * @var string
	 */
	const DEFAULT_VERSION = '7.3.0';

	public function __construct($parameters,
		ConnectionInterface $connection)
	{
		parent::__construct($parameters);
		$this->initializeStatementFactory();
		$this->setConnection($connection);

		$platformCreateTableFlags = 0;
		$platformCreateViewFlags = 0;
		$platformCreateNamespaceFlags = 0;

		$platformDropFlags = 0;

		$this->setPlatformFeature(
			[
				K::FEATURE_INSERT,
				K::FEATURE_INSERT_FLAGS
			],
			(K::FEATURE_INSERT_FLAG_DEFAULTVALUES |
			K::FEATURE_INSERT_FLAG_SELECT));

		$platformCreateTableFlags |= K::FEATURE_CREATE_TEMPORARY;

		$this->setPlatformFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_CONSTRAINT_DECLARATION_FLAGS
			], (K::FEATURE_CONSTRAINT_REFERENCES_QUALIFIED));

		$serverVersion = $this->getPlatformVersion();
		$compatibility = $serverVersion->slice(SemanticVersion::MAJOR,
			SemanticVersion::MAJOR);

		if (SemanticVersion::compareVersions($serverVersion, '7.3.0') >=
			0)
		{
			$platformDropFlags |= K::FEATURE_DROP_CASCADE;

			$compatibility = '7.3.0';
		}

		if (SemanticVersion::compareVersions($serverVersion, '8.1.0') >=
			0)
		{
			$platformCreateViewFlags |= K::FEATURE_CREATE_TEMPORARY;

			$compatibility = '8.1.0';
		}

		if (SemanticVersion::compareVersions($serverVersion, '8.2.0') >=
			0)
		{
			$platformDropFlags |= K::FEATURE_DROP_EXISTS_CONDITION;
			$compatibility = '8.2.0';
		}

		if (SemanticVersion::compareVersions($serverVersion, '9.1.0') >=
			0)
		{
			$platformCreateTableFlags |= K::FEATURE_CREATE_EXISTS_CONDITION;

			$compatibility = '9.1.0';
		}

		if (SemanticVersion::compareVersions($serverVersion, '9.3.0') >=
			0)
		{
			$platformCreateNamespaceFlags |= K::FEATURE_CREATE_EXISTS_CONDITION;
			$compatibility = '9.3.0';
		}

		if (SemanticVersion::compareVersions($serverVersion, '10.0.0') >=
			0)
		{
			$compatibility = '10.0.0';
		}

		$this->setPlatformFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_ELEMENT_NAMESPACE,
				K::FEATURE_CREATE_FLAGS
			], $platformCreateNamespaceFlags);

		$this->setPlatformFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_ELEMENT_TABLE,
				K::FEATURE_CREATE_FLAGS
			], $platformCreateTableFlags);

		$this->setPlatformFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_ELEMENT_VIEW,
				K::FEATURE_CREATE_FLAGS
			], $platformCreateViewFlags);

		$this->setPlatformFeature(
			[
				K::FEATURE_DROP,
				K::FEATURE_DROP_FLAGS
			], $platformDropFlags);

		$compatibility = ($compatibility instanceof SemanticVersion) ? $compatibility : new SemanticVersion(
			$compatibility);
		$this->setPlatformVersion(self::VERSION_COMPATIBILITY,
			$compatibility);
	}

	public function newConfigurator(ConnectionInterface $connection)
	{
		return new PostgreSQLConfigurator($this, $connection);
	}

	public function quoteBinaryData($value)
	{
		if (\is_int($value))
		{
			$value = \base_convert($value, 10, 16);
			if (\strlen($value) % 2 == 1)
			{
				$value = '0' . $value;
			}

			$value = \hex2bin($value);
		}

		return "'" . \pg_escape_bytea($value) . "'";
	}

	public function quoteIdentifier($identifier)
	{
		return $this->connection->quoteIdentifier($identifier);
	}

	public function getColumnType($columnDescription,
		$constraintFlags = 0)
	{
		$columnFlags = Container::keyValue($columnDescription,
			K::COLUMN_FLAGS, 0);
		// Special case for auto-increment column
		if ($columnFlags & K::COLUMN_FLAG_AUTO_INCREMENT)
		{
			return new BasicType('serial');
		}

		/**
		 *
		 * @var TypeRegistry $types
		 */
		$types = PostgreSQLTypeRegistry::getInstance();
		$matchingTypes = $types->matchDescription($columnDescription);
		return Container::firstValue($matchingTypes);
	}

	public function getTypeRegistry()
	{
		return PostgreSQLTypeRegistry::getInstance();
	}

	public function getParameter($name,
		$valueDataType = K::DATATYPE_UNDEFINED,
		ParameterData $parameters = null)
	{
		$key = strval($name);

		if (false)
		{
			/**
			 * Cannot re-use the same parameter number because it may
			 * produce "inconsistent types deduced for parameter"
			 */

			if ($parameters->has($key))
				return $parameters->get($key)[ParameterData::DBMSNAME];

			return '$' . ($parameters->getDistinctParameterCount() + 1);
		}

		$dbmsName = '$' . ($parameters->count() + 1);

		if ($valueDataType)
		{
			$registry = $this->getTypeRegistry();
			$types = $registry->matchDescription(
				[
					K::COLUMN_DATA_TYPE => $valueDataType
				]);
			$type = Container::firstValue($types);
			$dbmsName .= '::' . $type->get(K::TYPE_NAME);
		}

		return $dbmsName;
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
					],
					DateTime::FORMAT_TIMESTAMP_ISO8601 => [
						'IYYY-MM-DD"T"HH24:MI:SSOF',
						'Minute offset will not be included'
					],
					DateTime::FORMAT_TIMESTAMP_RFC2822 => [
						'DD Mon YYYY HH24:MI:SSOF',
						'Colon in timezone offset. Minute offset may not be included'
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

	/**
	 * <todo Polyfill of pg_unescape_bytea()
	 */
	protected function unserializeBinaryColumnData($column, $data)
	{
		if (\is_resource($data) && \get_resource_type($data))
			return \stream_get_contents($data);
		return \pg_unescape_bytea($data);
	}

	protected function unserializeBooleanColumnData($column, $data)
	{
		if (\is_string($data))
			return ($data == 't');
		return TypeConversion::toBoolean($data);
	}

	private function translateTimestampFunction(
		MetaFunctionCall $metaFunction)
	{
		$format = $metaFunction->getArgument(0);
		if ($format instanceof Value)
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

