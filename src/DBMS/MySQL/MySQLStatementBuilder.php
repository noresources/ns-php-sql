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
use NoreSources\SQL\DBMS\BasicType;
use NoreSources\SQL\DBMS\TypeHelper;
use NoreSources\SQL\DBMS\MySQL\MySQLConstants as K;
use NoreSources\SQL\Expression\FunctionCall;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Expression\MetaFunctionCall;
use NoreSources\SQL\Statement\ParameterMap;
use NoreSources\SQL\Statement\StatementBuilder;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\TableStructure;

// Aliases
class MySQLStatementBuilder extends StatementBuilder
{

	public function __construct(MySQLConnection $connection)
	{
		parent::__construct();
		$this->connection = $connection;

	/**
	 *
	 * @todo builder flags
	 */
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

	public function getParameter($name, ParameterMap $parameters = null)
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

	public static function getMySQLColumnTypeName(ColumnStructure $column)
	{
		$dataType = K::DATATYPE_UNDEFINED;
		if ($column->hasColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE))
			$dataType = $column->getColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE);

		/**
		 *
		 * @todo more accurate type selection based on size etc.
		 */

		if ($dataType & K::DATATYPE_TIMESTAMP)
		{
			if ($dataType == K::DATATYPE_DATE)
				return 'DATE';
			if ($dataType == K::DATATYPE_TIME)
				return 'TIME';

			return 'DATETIME';
		}

		switch ($dataType)
		{
			case K::DATATYPE_BINARY:
				return 'BLOB';
			case K::DATATYPE_NUMBER:
			case K::DATATYPE_FLOAT:
				return 'DOUBLE';
			case K::DATATYPE_BOOLEAN:
				return 'BOOLEAN';
			case K::DATATYPE_INTEGER:
				return 'INT';
			case K::DATATYPE_NULL:
				return 'NULL';
		}

		return 'TEXT';
	}

	public function getColumnType(ColumnStructure $column)
	{
		$types = MySQLType::getMySQLTypes();
		$count = Container::count($types);
		$table = $column->parent();

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
				Container::valueExists($pk->getColumns(), $column->getName()))
			{
				// Types must have a key length
				$types = Container::filter($types,
					function ($_, $type) {
						/**
						 *
						 * @var TypeInterface $type
						 */
						return ((TypeHelper::getProperty($type, K::TYPE_PROPERTY_FLAGS) &
						K::TYPE_FLAG_LENGTH) == K::TYPE_FLAG_LENGTH);
					});

				$count = Container::count($types);
			}
		}

		// Some types does not accepts non-null default value
		if ($column->hasColumnProperty(K::COLUMN_PROPERTY_DEFAULT_VALUE))
		{
			$dflt = $column->getColumnProperty(K::COLUMN_PROPERTY_DEFAULT_VALUE);
			if (!(($dflt instanceof Literal) && ($dflt->getExpressionDataType() == K::DATATYPE_NULL)))
			{
				$types = Container::filter($types,
					function ($_, $type) {
						return ((TypeHelper::getProperty($type, K::TYPE_PROPERTY_FLAGS) &
						K::TYPE_FLAG_DEFAULT_VALUE) == K::TYPE_FLAG_DEFAULT_VALUE);
					});

				$count = Container::count($types);
			}
		}

		$dataType = K::DATATYPE_UNDEFINED;
		if ($column->hasColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE))
			$dataType = $column->getColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE);

		if ($dataType != K::DATATYPE_UNDEFINED)
		{
			$types = Container::filter($types,
				function ($_, $type) use ($dataType) {
					if (!$type->has(K::TYPE_PROPERTY_DATA_TYPE))
						return false;
					$typeDataType = $type->get(K::TYPE_PROPERTY_DATA_TYPE);
					return (($typeDataType & $dataType) == $dataType);
				});

			$count = Container::count($types);
			if ($count == 0)
				throw new \RuntimeException(
					'No MySQL type found for column type ' . K::dataTypeName($dataType));
		}

		if ($count == 1)
		{
			list ($oid, $type) = each($types);
			return $type;
		}

		if ($column->hasColumnProperty(K::COLUMN_PROPERTY_LENGTH))
		{
			$glyphCount = intval($column->getColumnProperty(K::COLUMN_PROPERTY_LENGTH));

			$filtered = Container::filter($types,
				function ($_, $type) use ($dataType, $glyphCount) {
					return TypeHelper::getMaxGlyphCount($type) >= $glyphCount;
				});

			$c = Container::count($filtered);
			if ($c)
			{
				// Prefer the smallest type
				usort($filtered,
					function ($a, $b) {
						if ($a->has(K::TYPE_PROPERTY_SIZE) && $b->has(K::TYPE_PROPERTY_SIZE))
							return ($a->get(K::TYPE_PROPERTY_SIZE) - $b->get(K::TYPE_PROPERTY_SIZE));
						if ($a->has(K::TYPE_PROPERTY_FIXED_LENGTH) &&
						$b->has(K::TYPE_PROPERTY_FIXED_LENGTH))
							return ($a->get(K::TYPE_PROPERTY_FIXED_LENGTH) -
							$b->get(K::TYPE_PROPERTY_FIXED_LENGTH));
						return 0;
					});

				$count = $c;
				$types = $filtered;
			}
		}
		else
		{
			/**
			 *
			 * @todo remove types with MANDATORY glyph count
			 */

			// Prefer types without padding
			usort($types,
				function ($a, $b) {
					if ($a->has(K::TYPE_PROPERTY_PADDING_GLYPH))
						return ($b->has(K::TYPE_PROPERTY_PADDING_GLYPH) ? 0 : 1);

					return ($b->has(K::TYPE_PROPERTY_PADDING_GLYPH) ? 0 : -1);
				});
		}

		/**
		 *
		 * @todo Media type
		 */
		if ($count)
		{
			list ($oid, $type) = each($types);
			return $type;
		}

		return new BasicType('TEXT');
	}

	public function getKeyword($keyword)
	{
		/**
		 *
		 * @todo may require adjustements
		 */
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
		throw new \Exception(__METHOD__ . ' Not implemented');
		if (!Container::isArray(self::$timestampFormatTranslations))
		{
			self::$timestampFormatTranslations = new \ArrayObject(
				[
					'Y' => '%Y',
					'y' => [
						'%Y',
						'Two digits year number format is not available'
					],
					'o' => '%G',
					'L' => false,
					'M' => false,
					'F' => false,
					'm' => '%m',
					'n' => [
						'%m',
						'Month number without leading zero is not available'
					],
					'W' => '%W',
					'l' => false,
					't' => false,
					'D' => false,
					'd' => '%d',
					'j' => '%d',
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
		throw new \Exception(__METHOD__ . ' Not implemented');

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
					trigger_error('Timestamp format "' . $c . ' " nut supported by MySQL strftime',
						E_USER_WARNING);
					continue;
				}

				if (\is_array($t))
				{
					trigger_error('Timestamp format "' . $c . '": ' . $t[1], E_USER_NOTICE);
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