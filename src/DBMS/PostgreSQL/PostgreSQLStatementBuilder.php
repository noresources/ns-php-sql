<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\PostgreSQL;

use NoreSources\Container;
use NoreSources\SemanticVersion;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\BasicType;
use NoreSources\SQL\DBMS\TypeHelper;
use NoreSources\SQL\DBMS\Reference\ReferenceStatementBuilder;
use NoreSources\SQL\Expression\FunctionCall;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Expression\MetaFunctionCall;
use NoreSources\SQL\Statement\ParameterData;
use NoreSources\SQL\Statement\StatementBuilder;
use NoreSources\SQL\Structure\ColumnStructure;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class PostgreSQLStatementBuilder extends StatementBuilder implements LoggerAwareInterface
{

	use LoggerAwareTrait;

	public function __construct(PostgreSQLConnection $connection = null)
	{
		parent::__construct();
		$this->connection = $connection;

		$this->setBuilderFlags(K::BUILDER_DOMAIN_INSERT,
			K::BUILDER_INSERT_DEFAULT_VALUES | K::BUILDER_INSERT_DEFAULT_KEYWORD);
	}

	public function serializeString($value)
	{
		$resource = $this->getConnectionResource();
		$result = false;
		if (\is_resource($resource))
			$result = @\pg_escape_literal($resource, $value);

		if ($result !== false)
			return $result;

		return "'" . \pg_escape_string($value) . "'";
	}

	public function serializeBinary($value)
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

	public function escapeIdentifier($identifier)
	{
		$resource = $this->getConnectionResource();
		$result = false;
		if (\is_resource($resource))
			$result = \pg_escape_identifier($resource, $identifier);

		if ($result !== false)
			return $result;

		return ReferenceStatementBuilder::escapeIdentifierFallback($identifier, '"', '"');
	}

	public function getParameter($name, ParameterData $parameters = null)
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

		return '$' . ($parameters->getParameterCount() + 1);
	}

	public function translateFunction(MetaFunctionCall $metaFunction)
	{
		if ($metaFunction->getFunctionName() == K::METAFUNCTION_TIMESTAMP_FORMAT)
		{
			return $this->translateTimestampFunction($metaFunction);
		}

		return parent::translateFunction($metaFunction);
	}

	public function getColumnType(ColumnStructure $column)
	{
		$columnFlags = $column->getColumnProperty(K::COLUMN_FLAGS);

		// Special case for auto-increment column
		if ($columnFlags & K::COLUMN_FLAG_AUTO_INCREMENT)
		{
			return new BasicType('serial');
		}

		$types = PostgreSQLType::getPostgreSQLTypes();
		$matchingTypes = TypeHelper::getMatchingTypes($column, $types);

		list ($k, $type) = each($matchingTypes);
		return $type;
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

	public function getConnectionResource()
	{
		if ($this->connection instanceof PostgreSQLConnection)
		{
			if (\is_resource($this->connection->getConnectionResource()))
				return $this->connection->getConnectionResource();
		}

		return null;
	}

	/**
	 * Update builder flags according PostgreSQL server version
	 *
	 * @param SemanticVersion $serverVersion
	 *        	PostgreSQL server version
	 */
	public function updateBuilderFlags(SemanticVersion $serverVersion)
	{
		$createTableFlags = $this->getBuilderFlags(K::BUILDER_DOMAIN_CREATE_TABLE);
		$dropTableFlags = $this->getBuilderFlags(K::BUILDER_DOMAIN_DROP_TABLE);
		$createNamespaceFlags = $this->getBuilderFlags(K::BUILDER_DOMAIN_CREATE_NAMESPACE);

		$dropTableFlags &= ~(K::BUILDER_IF_EXISTS | K::BUILDER_DROP_CASCADE);
		$createTableFlags &= ~(K::BUILDER_IF_NOT_EXISTS);
		$createNamespaceFlags &= ~(K::BUILDER_IF_NOT_EXISTS);

		if (SemanticVersion::compareVersions($serverVersion, '7.3.0') >= 0)
		{
			$dropTableFlags |= K::BUILDER_DROP_CASCADE;
		}

		if (SemanticVersion::compareVersions($serverVersion, '8.2.0') >= 0)
		{
			$dropTableFlags |= K::BUILDER_IF_EXISTS;
		}

		if (SemanticVersion::compareVersions($serverVersion, '9.1.0') >= 0)
		{
			$createTableFlags |= K::BUILDER_IF_NOT_EXISTS;
		}

		if (SemanticVersion::compareVersions($serverVersion, '9.3.0') >= 0)
		{
			$createNamespaceFlags |= K::BUILDER_IF_NOT_EXISTS;
		}

		$this->setBuilderFlags(K::BUILDER_DOMAIN_CREATE_TABLE, $createTableFlags);
		$this->setBuilderFlags(K::BUILDER_DOMAIN_CREATE_NAMESPACE, $createNamespaceFlags);
		$this->setBuilderFlags(K::BUILDER_DOMAIN_DROP_TABLE, $dropTableFlags);
	}

	private function translateTimestampFunction(MetaFunctionCall $metaFunction)
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
				if (Container::keyExists(self::getTimestampFormatTranslations(), $c))
				{
					$t = Container::keyValue(self::getTimestampFormatTranslations(), $c, $c);

					if ($quoted)
						$translation .= '"';

					$quoted = false;

					if ($t === false)
					{
						$this->logger->warning(
							'Timestamp format "' . $c . '" nut supported by PostgreSQL to_char');
						continue;
					}

					if (\is_array($t))
					{
						$this->logger->notice('Timestamp format "' . $c . '": ' . $t[1]);
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
	 * @return \ArrayObject
	 */
	public static function getTimestampFormatTranslations()
	{
		if (!Container::isArray(self::$timestampFormatTranslations))
		{
			self::$timestampFormatTranslations = new \ArrayObject(
				[
					'Y' => 'YYYY',
					'y' => 'YY',
					'o' => 'IYYY',
					'L' => false,
					'M' => 'Mon',
					'F' => 'FMMonth',
					'm' => 'MM',
					'n' => 'FMMM',
					'W' => 'IW',
					'l' => 'FMDay',
					't' => false,
					'D' => 'Dy',
					'd' => 'DD',
					'j' => 'FMDD',
					'z' => [
						'DDD',
						'Day of year range will be [1-366] instead of [0-365]'
					],
					'N' => 'ID',
					'S' => false,
					'w' => [
						'D',
						'Day of week range will be [1-7] instead of [0-6]'
					],
					// Hours
					'H' => 'HH24',
					'G' => 'FMHH24',
					'h' => 'HH',
					'g' => 'FMHH',
					'B' => false,
					'A' => 'AM',
					'a' => 'am',
					// Minutes
					'i' => 'MI',
					// Seconds
					's' => 'SS',
					'v' => 'MS',
					'u' => 'US',
					// Time zone
					'Z' => false,
					'O' => false,
					'P' => [
						'OF',
						'Minute offset will not be included'
					],
					'e' => false,
					'T' => [
						'TZ',
						'Timezone abbreviations may differ and are not available on timestamp without timezone'
					],
					'I' => false,
					'r' => false,
					'c' => [
						'YYY-MM-DD"T"HH24:MI:SSOF',
						'Time zone offset will contain colon(s)'
					],
					'U' => false
				]);
		}

		return self::$timestampFormatTranslations;
	}

	/**
	 *
	 * @var PostgreSQLConnection
	 */
	private $connection;

	private static $timestampFormatTranslations;
}