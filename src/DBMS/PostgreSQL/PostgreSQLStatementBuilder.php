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
use NoreSources\SQL\DBMS\Reference\ReferenceStatementBuilder;
use NoreSources\SQL\Expression\FunctionCall;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Expression\MetaFunctionCall;
use NoreSources\SQL\Statement\ParameterMap;
use NoreSources\SQL\Statement\StatementBuilder;
use NoreSources\SQL\Structure\ColumnStructure;

// Aliases
class PostgreSQLStatementBuilder extends StatementBuilder
{

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

	public function getParameter($name, ParameterMap $parameters = null)
	{
		$name = strval($name);
		if ($parameters->offsetExists($name))
		{
			return $parameters->offsetGet($name);
		}

		return '$' . ($parameters->getNamedParameterCount() + 1);
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
		return PostgreSQLType::columnPropertyToType($column);
	}

	public function getKeyword($keyword)
	{
		switch ($keyword)
		{
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
		$createTablesetFlags = $this->getBuilderFlags(K::BUILDER_DOMAIN_CREATE_TABLESET);

		$createTableFlags &= ~(K::BUILDER_IF_NOT_EXISTS);
		$createTablesetFlags &= ~K::BUILDER_IF_NOT_EXISTS;

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
			$createTablesetFlags |= K::BUILDER_IF_NOT_EXISTS;
		}

		$this->setBuilderFlags(K::BUILDER_DOMAIN_CREATE_TABLE, $createTableFlags);
		$this->setBuilderFlags(K::BUILDER_DOMAIN_CREATE_TABLESET, $createTablesetFlags);
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
						trigger_error(
							'Timestamp format "' . $c . ' " nut supported by SQLite to_char',
							E_USER_WARNING);
						continue;
					}

					if (\is_array($t))
					{
						trigger_error('Timestamp format "' . $c . '": ' . $t[1], E_USER_NOTICE);
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

	public static function getTimestampFormatTranslations()
	{
		if (!\is_array(self::$timestampFormatTranslations))
		{
			self::$timestampFormatTranslations = [
				'Y' => 'YYYY',
				'y' => 'YY',
				'o' => 'IYYY',
				'L' => false,
				'M' => 'Mon',
				'F' => 'Month',
				'm' => 'MM',
				'n' => 'FMMM',
				'W' => 'IW',
				'l' => 'Day',
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
				'P' => 'OF',
				'e' => false,
				'T' => 'TZ',
				'I' => false,
				'r' => false,
				'c' => [
					'YYY-MM-DD"T"HH24:MI:SSOF',
					'Time zone offset will contain colon(s)'
				],
				'U' => false
			];
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