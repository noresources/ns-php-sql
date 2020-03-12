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

use NoreSources\DateTime;
use NoreSources\TypeConversion;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\FunctionCall;
use NoreSources\SQL\Expression\MetaFunctionCall;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContext;
use NoreSources\SQL\Structure\ColumnPropertyMap;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\ColumnTableConstraint;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\StructureElement;
use NoreSources\SQL\Structure\TableConstraint;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\UniqueTableConstraint;
use NoreSources as ns;

/**
 * Generic, partial implementation of StatementBuilderInterface.
 *
 * This should be used as base class for all DBMS-specific statement builders.
 */
abstract class StatementBuilder implements StatementBuilderInterface
{

	/**
	 *
	 * @param number $flags
	 *        	StatementBuilder flags
	 */
	public function __construct()
	{
		$this->builderFlags = [
			K::BUILDER_DOMAIN_GENERIC => 0,
			K::BUILDER_DOMAIN_SELECT => 0,
			K::BUILDER_DOMAIN_INSERT => 0,
			K::BUILDER_DOMAIN_UPDATE => 0,
			K::BUILDER_DOMAIN_DELETE => 0,
			K::BUILDER_DOMAIN_DROP_TABLE => 0,
			K::BUILDER_DOMAIN_CREATE_TABLE => 0,
			K::BUILDER_DOMAIN_CREATE_TABLESET => 0
		];
	}

	public function getBuilderFlags($domain = K::BUILDER_DOMAIN_GENERIC)
	{
		return $this->builderFlags[$domain];
	}

	public function serializeBinary($value)
	{
		return $this->serializeString($value);
	}

	public function serializeTimestamp($value, $type = K::DATATYPE_TIMESTAMP)
	{
		if (\is_int($value) || \is_float($value) || \is_string($value))
			$value = new DateTime($value);
		elseif (DateTime::isDateTimeStateArray($value))
			$value = DateTime::createFromArray($value);

		if ($value instanceof \DateTime)
			return $this->serializeString($value->format($this->getTimestampFormat($type)));
	}

	public function getColumnTypeName(ColumnStructure $column)
	{
		return $this->getColumnType($column)->getTypeName();
	}

	public function translateFunction(MetaFunctionCall $metaFunction)
	{
		return new FunctionCall($metaFunction->getFunctionName(), $metaFunction->getArguments());
	}

	public function getKeyword($keyword)
	{
		switch ($keyword)
		{
			case K::KEYWORD_AUTOINCREMENT:
				return 'AUTO INCREMENT';
			case K::KEYWORD_CURRENT_TIMESTAMP:
				return 'CURRENT_TIMESTAMP';
			case K::KEYWORD_NULL:
				return 'NULL';
			case K::KEYWORD_TRUE:
				return 'TRUE';
			case K::KEYWORD_FALSE:
				return 'FALSE';
			case K::KEYWORD_DEFAULT:
				return 'DEFAULT';
		}

		throw new \InvalidArgumentException('Keyword ' . $keyword . ' is not available');
	}

	public function getJoinOperator($joinTypeFlags)
	{
		$s = '';
		if (($joinTypeFlags & K::JOIN_NATURAL) == K::JOIN_NATURAL)
			$s .= 'NATURAL ';

		if (($joinTypeFlags & K::JOIN_LEFT) == K::JOIN_LEFT)
		{
			$s . 'LEFT ';
			if (($joinTypeFlags & K::JOIN_OUTER) == K::JOIN_OUTER)
			{
				$s .= 'OUTER ';
			}
		}
		elseif (($joinTypeFlags & K::JOIN_RIGHT) == K::JOIN_RIGHT)
		{
			$s . 'RIGHT ';
			if (($joinTypeFlags & K::JOIN_OUTER) == K::JOIN_OUTER)
			{
				$s .= 'OUTER ';
			}
		}
		else
			if (($joinTypeFlags & K::JOIN_CROSS) == K::JOIN_CROSS)
			{
				$s .= 'CROSS ';
			}
			else
				if (($joinTypeFlags & K::JOIN_INNER) == K::JOIN_INNER)
				{
					$s .= 'INNER ';
				}

		return ($s . 'JOIN');
	}

	public function getColumnTypeDeclaration(ColumnStructure $column)
	{}

	public function getTimestampFormat($type = 0)
	{
		switch ($type)
		{
			case K::DATATYPE_DATE:
				return 'Y-m-d';
			case K::DATATYPE_TIME:
				return 'H:i:s';
			case K::DATATYPE_TIMEZONE:
				return 'H:i:sO';
			case K::DATATYPE_DATETIME:
				return 'Y-m-d\TH:i:s';
		}

		return \DateTime::ISO8601;
	}

	public function getTableConstraintDescription(TableStructure $structure,
		TableConstraint $constraint)
	{
		$s = '';
		if (strlen($constraint->constraintName))
		{
			$s .= 'CONSTRAINT ' . $this->escapeIdentifier($constraint->constraintName) . ' ';
		}

		if ($constraint instanceof ColumnTableConstraint)
		{
			if ($constraint instanceof PrimaryKeyTableConstraint)
				$s .= 'PRIMARY KEY';
			elseif ($constraint instanceof UniqueTableConstraint)
				$v .= 'UNIQUE';
			$columns = [];

			foreach ($constraint as $column)
			{
				$columns[] = $this->escapeIdentifier($column->getName());
			}

			$s .= ' (' . implode(', ', $columns) . ')';
		}
		elseif ($constraint instanceof ForeignKeyTableConstraint)
		{
			$s .= 'FOREIGN KEY';
			if ($constraint->count())
			{
				$s .= ' (';
				$s .= ns\Container::implodeKeys($constraint->columns, ', ',
					[
						$this,
						'escapeIdentifier'
					]);
				$s .= ')';
			}
			$s .= ' REFERENCES ' . $this->getCanonicalName($constraint->foreignTable);
			if ($constraint->count())
			{
				$s .= ' (';
				$s .= ns\Container::implodeValues($constraint->columns, ', ',
					[
						$this,
						'escapeIdentifier'
					]);
				$s .= ')';
			}

			if ($constraint->onUpdate)
			{
				$s .= ' ON UPDATE ' . $this->getForeignKeyAction($constraint->onUpdate);
			}

			if ($constraint->onDelete)
			{
				$s .= ' ON DELETE ' . $this->getForeignKeyAction($constraint->onDelete);
			}
		}

		return $s;
	}

	public function serializeColumnData(ColumnPropertyMap $column, $value)
	{
		$type = K::DATATYPE_UNDEFINED;
		if ($column->hasColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE))
			$type = $column->getColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE);

		if ($column->hasColumnProperty(K::COLUMN_PROPERTY_MEDIA_TYPE))
		{
			$mediaType = $column->getColumnProperty(K::COLUMN_PROPERTY_MEDIA_TYPE);
			if ($mediaType instanceof ns\MediaType)
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
				return $this->getKeyword(K::KEYWORD_NULL);
			case K::DATATYPE_BINARY:
				return $this->serializeBinary($value);
			case K::DATATYPE_BOOLEAN:
				return $this->getKeyword(
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
			$value = $value->format($this->getTimestampFormat(K::DATATYPE_TIMESTAMP));

		return $this->serializeString(TypeConversion::toString($value));
	}

	public function escapeIdentifierPath($path)
	{
		return ns\Container::implodeValues($path, '.', [
			$this,
			'escapeIdentifier'
		]);
	}

	public function getCanonicalName(StructureElement $structure)
	{
		$s = $this->escapeIdentifier($structure->getName());
		$p = $structure->parent();
		while ($p && !($p instanceof DatasourceStructure))
		{
			$s = $this->escapeIdentifier($p->getName()) . '.' . $s;
			$p = $p->parent();
		}

		return $s;
	}

	public function finalizeStatement(TokenStream $stream, TokenStreamContext &$context)
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
				$position = $data->getParameters()->count();
				$dbmsName = $this->getParameter($name, $data->getParameters());
				$data->registerParameter($position, $name, $dbmsName);
				$value = $dbmsName;
			}

			$sql .= $value;
		}

		$data->setSQL($sql);
		return $data;
	}

	public function getForeignKeyAction($action)
	{
		switch ($action)
		{
			case K::FOREIGN_KEY_ACTION_CASCADE:
				return 'CASCADE';
			case K::FOREIGN_KEY_ACTION_RESTRICT:
				return 'RESTRICT';
			case K::FOREIGN_KEY_ACTION_SET_DEFAULT:
				return 'SET DEFAULT';
			case K::FOREIGN_KEY_ACTION_SET_NULL:
				'SET NULL';
		}
		return 'NO ACTION';
	}

	/**
	 * Fallback string escaping function.
	 * Used when the DBMS does not provide text escaping method.
	 *
	 * Contrary to serializeString(), this function DOES NOT add single quote around the resulting text.
	 *
	 * @param string $text
	 * @return string
	 */
	public static function escapeString($text)
	{
		if (\function_exists('pg_escape_string'))
			return \pg_escape_string($text);
		elseif (\class_exists('\SQLite3'))
			return \SQLite3::escapeString($text);

		return \str_replace("'" . $text . "'");
	}

	protected function setBuilderFlags($domain, $flags)
	{
		$this->builderFlags[$domain] = $flags;
	}

	/**
	 *
	 * @var array
	 */
	private $builderFlags;
}
