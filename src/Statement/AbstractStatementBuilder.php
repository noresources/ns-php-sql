<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Statement;

use NoreSources\Container;
use NoreSources\DateTime;
use NoreSources\TypeConversion;
use NoreSources\MediaType\MediaType;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\FunctionCall;
use NoreSources\SQL\Expression\MetaFunctionCall;
use NoreSources\SQL\Expression\StructureElementIdentifier;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Structure\ColumnDescriptionInterface;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\StructureElementInterface;

/**
 * Generic, partial implementation of StatementBuilderInterface.
 *
 * This should be used as base class for all DBMS-specific statement builders.
 */
abstract class AbstractStatementBuilder implements
	StatementBuilderInterface
{

	/**
	 *
	 * @param number $flags
	 *        	AbstractStatementBuilder flags
	 */
	public function __construct()
	{}

	public function serializeColumnData(
		ColumnDescriptionInterface $column, $value)
	{
		$type = K::DATATYPE_UNDEFINED;
		if ($column->hasColumnProperty(K::COLUMN_DATA_TYPE))
			$type = $column->getColumnProperty(K::COLUMN_DATA_TYPE);

		if ($column->hasColumnProperty(K::COLUMN_MEDIA_TYPE))
		{
			$mediaType = $column->getColumnProperty(
				K::COLUMN_MEDIA_TYPE);
			if ($mediaType instanceof MediaType)
			{
				if ($mediaType->getStructuredSyntax() == 'json')
				{
					if ($value instanceof \JsonSerializable)
						$value = $value->jsonSerialize();
					else
						$value = json_encode($value);
				}
			}
		}

		switch ($type)
		{
			case K::DATATYPE_NULL:
				return $this->getPlatform()->getKeyword(K::KEYWORD_NULL);
			case K::DATATYPE_BINARY:
				return $this->serializeBinary($value);
			case K::DATATYPE_BOOLEAN:
				return $this->getPlatform()->getKeyword(
					TypeConversion::toBoolean($value) ? K::KEYWORD_TRUE : K::KEYWORD_FALSE);
			case K::DATATYPE_INTEGER:
				return TypeConversion::toInteger($value);
			case K::DATATYPE_FLOAT:
			case K::DATATYPE_NUMBER:
				return TypeCOnversion::toFloat($value);
		}

		if ($type & K::DATATYPE_TIMESTAMP)
			return $this->serializeTimestamp($value, $type);

		if ($value instanceof \DateTimeInterface)
			$value = $value->format(
				$this->getPlatform()
					->getTimestampTypeStringFormat(
					K::DATATYPE_TIMESTAMP));

		return $this->serializeString(TypeConversion::toString($value));
	}

	public function translateFunction(MetaFunctionCall $metaFunction)
	{
		return new FunctionCall($metaFunction->getFunctionName(),
			$metaFunction->getArguments());
	}

	public function getCanonicalName($structure)
	{
		if (\is_string($structure))
			return $this->escapeString($structure);

		if ($structure instanceof StructureElementInterface)
		{
			$s = $this->escapeIdentifier($structure->getName());
			$p = $structure->getParentElement();
			while ($p && !($p instanceof DatasourceStructure))
			{
				$s = $this->escapeIdentifier($p->getName()) . '.' . $s;
				$p = $p->getParentElement();
			}

			return $s;
		}

		if ($structure instanceof StructureElementIdentifier)
			$structure = $structure->getPathParts();

		return Container::implodeValues($structure, '.',
			function ($v) {
				return $this->escapeIdentifier($v);
			});
	}

	public function finalizeStatement(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$data = new StatementData($context);
		$sql = '';

		foreach ($stream as $token)
		{
			$value = $token[TokenStream::INDEX_TOKEN];
			$type = $token[TokenStream::INDEX_TYPE];

			if ($type == K::TOKEN_PARAMETER)
			{
				$name = \strval($value);
				$dbmsName = $this->getParameter($name,
					$data->getParameters());
				$position = $data->getParameters()->appendParameter(
					$name, $dbmsName);
				$value = $dbmsName;
			}

			$sql .= $value;
		}

		$data->setSQL($sql);
		return $data;
	}

	/**
	 * Escape text string to be inserted in a SQL statement.
	 *
	 * @param string $value
	 *        	A quoted string with escaped characters
	 */
	public function serializeString($value)
	{
		return "'" . self::escapeString($value) . "'";
	}

	/**
	 * Escape binary data to be inserted in a SQL statement.
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function serializeBinary($value)
	{
		return $this->serializeString($value);
	}

	public function serializeTimestamp($value,
		$type = K::DATATYPE_TIMESTAMP)
	{
		if (\is_int($value) || \is_float($value) || \is_string($value))
			$value = new DateTime($value);
		elseif (DateTime::isDateTimeStateArray($value))
			$value = DateTime::createFromArray($value);

		if ($value instanceof \DateTimeInterface)
			$value = $value->format(
				$this->getPlatform()
					->getTimestampTypeStringFormat($type));
		else
			$value = TypeConversion::toString($value);

		return $this->serializeString($value);
	}

	/**
	 * Fallback string escaping function.
	 * Used when the DBMS does not provide text escaping method.
	 *
	 * Contrary to serializeString(), this function DOES NOT add single quote around the resulting
	 * text.
	 *
	 * @param string $text
	 * @return string
	 */
	protected static function escapeString($text)
	{
		if (\function_exists('pg_escape_string'))
			return \pg_escape_string($text);
		elseif (\class_exists('\SQLite3'))
			return \SQLite3::escapeString($text);

		return \str_replace("'" . $text . "'");
	}
}
