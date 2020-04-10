<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\Container;
use NoreSources\TypeConversion;
use NoreSources\SQL\DBMS\ArrayObjectType;
use NoreSources\SQL\DBMS\BasicType;
use NoreSources\SQL\DBMS\TypeHelper;
use NoreSources\SQL\DBMS\MySQL\MySQLConstants as K;
use NoreSources\SQL\Expression\FunctionCall;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Expression\MetaFunctionCall;
use NoreSources\SQL\Statement\ParameterData;
use NoreSources\SQL\Statement\StatementBuilder;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\TableStructure;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

// Aliases
class MySQLStatementBuilder extends StatementBuilder implements LoggerAwareInterface
{

	use LoggerAwareTrait;

	public function __construct(MySQLConnection $connection)
	{
		parent::__construct();
		$this->connection = $connection;

		/**
		 *
		 * @todo builder flags
		 */

		$createTableFlags = K::BUILDER_CREATE_PRIMARY_KEY_MANDATORY_LENGTH;
		$this->setBuilderFlags(K::BUILDER_DOMAIN_CREATE_TABLE, $createTableFlags);
	}

	public function serializeString($value)
	{
		if ($this->connection->isConnected())
			return "'" . $this->getLink()->real_escape_string($value) . "'";

		return "'" . self::escapeString($value) . "'";
	}

	public function serializeBinary($value)
	{
		if (\is_integer($value) || \is_float($value) || \is_null($value))
			return $value;

		if ($value instanceof \DateTimeInterface)
			$value = $value->format($this->getTimestampFormat(K::DATATYPE_TIMESTAMP));
		else
			$value = TypeConversion::toString($value);

		return $this->serializeString($value);
	}

	public function escapeIdentifier($identifier)
	{
		return '`' . $identifier . '`';
	}

	public function getParameter($name, ParameterData $parameters = null)
	{
		return '?';
	}

	public function translateFunction(MetaFunctionCall $metaFunction)
	{
		if ($metaFunction->getFunctionName() == K::METAFUNCTION_TIMESTAMP_FORMAT)
		{
			return $this->translateTimestampFormatFunction($metaFunction);
		}

		return parent::translateFunction($metaFunction);
	}

	public function getColumnType(ColumnStructure $column)
	{
		$types = MySQLType::getMySQLTypes();
		$table = $column->getParent();
		$isPrimaryKey = false;

		if ($table instanceof TableStructure)
		{
			$pk = null;
			foreach ($table->getConstraints() as $contraint)
			{
				if ($contraint instanceof PrimaryKeyTableConstraint)
				{
					$pk = $contraint;
					break;
				}
			}

			if ($pk instanceof PrimaryKeyTableConstraint &&
				Container::keyExists($pk->getColumns(), $column->getName()))
			{
				$isPrimaryKey = true;
				// Types must have a key length
				$types = Container::filter($types,
					function ($_, $type) {
						/**
						 *
						 * @var TypeInterface $type
						 */

						if ((TypeHelper::getProperty($type, K::TYPE_FLAGS) & K::TYPE_FLAG_LENGTH) ==
						K::TYPE_FLAG_LENGTH)
						{
							$maxLength = TypeHelper::getMaxLength($type);
							return !\is_infinite($maxLength);
						}
						return false;
					});
			}
		}

		$types = TypeHelper::getMatchingTypes($column, $types);

		if (Container::count($types) > 0)
		{
			list ($key, $type) = each($types);
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
				$keyMaxLength = intval(floor(K::KEY_MAX_LENGTH / $glyphLength));
				$typeMaxLength = TypeHelper::getMaxLength($type);

				if ($typeMaxLength > $keyMaxLength)
				{
					$type = new ArrayObjectType(
						\array_merge($type->getArrayCopy(), [
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
			case K::KEYWORD_AUTOINCREMENT:
				return 'AUTO_INCREMENT';
		}
		return parent::getKeyword($keyword);
	}

	/**
	 */
	public function getTimestampFormat($type = 0)
	{
		if ($type == K::DATATYPE_TIMESTAMP)
			return 'Y-m-d H:i:s';
		elseif ($type == (K::DATATYPE_TIME | K::DATATYPE_TIMEZONE))
			return 'H:i:s';

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
					// YEAR
					'Y' => '%Y',
					'y' => '%y',
					'o' => false,
					'L' => false,

					// Month

					// Abbreviated month name, based on the locale (an alias of %b)
					'M' => '%b',
					// Full month name, based on the locale
					'F' => '%M',
					// Two digit representation of the month
					'm' => '%m',
					// Month number without leading zero
					'n' => '%c',
					// ISO week number of the year
					'W' => '%v',
					// A full textual representation of the day
					'l' => '%W',
					// Number of day in the current month
					't' => false,
					// An abbreviated textual representation of the day
					'D' => '%a',
					// Two-digit day of the month (with leading zeros)
					'd' => '%d',
					'j' => '%e',
					'z' => [
						'%j',
						'Day of year range will be [1-366] instead of [0-365]'
					],
					// Day of the year, 3 digits with leading zeros
					'N' => [
						'%w',
						'Week day "sunday" will be 0 instead of 7'
					],
					// English ordinal suffix for the day of the month, 2 character
					'S' => false,
					// Numeric representation of the day of the week
					'w' => '%w',

					// Hours
					'H' => '%H',
					// Hour in 24-hour format, with a space preceding single digits
					'G' => '%k',
					// Two digit representation of the hour in 12-hour format
					'h' => '%h',
					// Hour in 12-hour format, with a space preceding single digits
					'g' => '%l',
					// Swatch internet time
					'B' => false,
					// UPPER-CASE 'AM' or 'PM' based on the given time
					'A' => '%p',
					// lower case am/pm
					'a' => false,

					// Minutes
					'i' => '%i',

					's' => '%S',
					// Milliseconds
					'v' => false,
					// Microseconds
					'u' => '%f',
					'Z' => false,
					'O' => false,
					'P' => false,
					'e' => false,
					'T' => false,
					'I' => false,
					'r' => false,
					'c' => false,
					'U' => false
				]);
		}

		return self::$timestampFormatTranslations;
	}

	public static function dataTypeFromMysqlType($mysqlTypeId)
	{
		switch ($mysqlTypeId)
		{
			case MYSQLI_TYPE_DECIMAL:
			case MYSQLI_TYPE_NEWDECIMAL:
			case MYSQLI_TYPE_FLOAT:
			case MYSQLI_TYPE_DOUBLE:
				return K::DATATYPE_FLOAT;

			case MYSQLI_TYPE_BIT:
				return K::DATATYPE_BOOLE;

			case MYSQLI_TYPE_TINY:
			case MYSQLI_TYPE_SHORT:
			case MYSQLI_TYPE_LONG:
			case MYSQLI_TYPE_LONGLONG:
			case MYSQLI_TYPE_INT24:
				return K::DATATYPE_INTEGER;

			case MYSQLI_TYPE_NULL:
				return K::DATATYPE_NULL;

			case MYSQLI_TYPE_TIMESTAMP:
				return K::DATATYPE_TIMESTAMP;

			case MYSQLI_TYPE_DATE:
			case MYSQLI_TYPE_NEWDATE:
			case MYSQLI_TYPE_YEAR:
				return K::DATATYPE_DATE;

			case MYSQLI_TYPE_TIME:
				return K::DATATYPE_TIME;

			case MYSQLI_TYPE_DATETIME:
				return K::DATATYPE_DATETIME;

			case MYSQLI_TYPE_TINY_BLOB:
			case MYSQLI_TYPE_MEDIUM_BLOB:
			case MYSQLI_TYPE_LONG_BLOB:
			case MYSQLI_TYPE_BLOB:
				return K::DATATYPE_BINARY;
		}

		return K::DATATYPE_STRING;
	}

	private function translateTimestampFormatFunction(MetaFunctionCall $metaFunction)
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

				$t = Container::keyValue(self::getTimestampFormatTranslations(), $c, $c);

				if ($t === false)
				{
					$this->logger->warning(
						'Timestamp format "' . $c . '" not supported by MySQL date_format()');
					continue;
				}

				if (\is_array($t))
				{
					$this->logger->notice('Timestamp format "' . $c . '": ' . $t[1]);
					$t = $t[0];
				}

				$translation .= $t;
			}

			$format->setValue($translation);
		}

		$timestamp = $metaFunction->getArgument(1);
		$strftime = new FunctionCall('date_format', [
			$timestamp,
			$format
		]);

		return $strftime;
	}

	protected function getLink()
	{
		return $this->connection->getServerLink();
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private static $timestampFormatTranslations;

	/**
	 *
	 * @var MySQLConnection
	 */
	private $connection;
}