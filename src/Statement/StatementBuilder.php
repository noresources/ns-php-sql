<?php
/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Statement;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataSerializer;
use NoreSources\SQL\Expression\TokenStream;
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
 * Build a SQL statement string to be used in a SQL engine
 */
abstract class StatementBuilder implements DataSerializer
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
			K::BUILDER_DOMAIN_CREATE_TABLE => 0
		];
	}

	/**
	 *
	 * @return number
	 */
	public function getBuilderFlags($domain = K::BUILDER_DOMAIN_GENERIC)
	{
		return $this->builderFlags[$domain];
	}

	/**
	 * Escape text string to be inserted in a SQL statement.
	 *
	 * @param string $value
	 */
	abstract function escapeString($value);

	/**
	 * Escape SQL identifier to be inserted in a SQL statement.
	 *
	 * @param string $identifier
	 */
	abstract function escapeIdentifier($identifier);

	/**
	 * Get a DBMS-compliant parameter name
	 *
	 * @param string $name
	 *        	Parameter name
	 * @param integer $position
	 *        	Total number of parameter
	 */
	abstract function getParameter($name, $position);

	/**
	 * Get the default type name for a given data type
	 *
	 * @param ColumnStructure $column
	 *        	Column definition
	 * @return string The default Connection type name for the given data type
	 */
	abstract function getColumnTypeName(ColumnStructure $column);

	/**
	 * Get syntax keyword.
	 *
	 * @param integer $keyword
	 * @throws \InvalidArgumentException
	 * @return string
	 */
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

	/**
	 *
	 * @param integer $joinTypeFlags
	 *        	JOIN type flags
	 * @return string
	 */
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
		else
			if (($joinTypeFlags & K::JOIN_RIGHT) == K::JOIN_RIGHT)
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

	/**
	 * Get the \DateTime timestamp format accepted by the Connection
	 *
	 * @param integer $type
	 *        	Timestamp parts. Combination of
	 *        	<ul>
	 *        	<li>Constants\DATATYPE_DATE</li>
	 *        	<li>Constants\DATATYPE_TIME</li>
	 *        	<li>Constants\DATATYPE_TIMEZONE</li>
	 *        	</ul>
	 *
	 * @return string \DateTime format string
	 */
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

	/**
	 * Build a partial SQL statement describing a table constraint in a CREATE TABLE statement.
	 *
	 * @param TableStructure $structure
	 * @param TableConstraint $constraint
	 * @return string
	 */
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
					$value = json_encode($value);
				}
			}
		}

		if ($type == K::DATATYPE_NULL)
			return $this->getKeyword(K::KEYWORD_NULL);

		if ($type == K::DATATYPE_BOOLEAN)
			return $this->getKeyword(($value) ? K::KEYWORD_TRUE : K::KEYWORD_FALSE);

		if ($value instanceof \DateTime)
		{
			if ($type & K::DATATYPE_NUMBER)
			{
				$ts = $value->getTimestamp();
				if ($type == K::DATATYPE_FLOAT)
				{
					$u = intval($value->format('u'));
					$ts += ($u / 1000000);
				}
				$value = $ts;
			}
			else
				$value = $value->format($this->getTimestampFormat($type));
		}

		if ($type & K::DATATYPE_TIMESTAMP)
		{
			if (\is_float($value))
			{
				$d = new \DateTime();
				$d->setTimestamp(jdtounix($value));
				$value = $d->format($this->getTimestampFormat($type));
			}
			elseif (\is_int($value))
			{
				$d = new \DateTime();
				$d->setTimestamp($value);
				$value = $d->format($this->getTimestampFormat($type));
			}
		}
		elseif ($type & K::DATATYPE_NUMBER)
		{
			if ($type & K::DATATYPE_INTEGER == K::DATATYPE_INTEGER)
				$value = intval($value);
			else
				$value = floatval($value);
			return $value;
		}

		return "'" . $this->escapeString($value) . "'";
	}

	/**
	 *
	 * @param array $path
	 * @return string
	 */
	public function escapeIdentifierPath($path)
	{
		return ns\Container::implodeValues($path, '.', [
			$this,
			'escapeIdentifier'
		]);
	}

	/**
	 *
	 * @param StructureElement $structure
	 * @return string
	 */
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

	/**
	 * Finalize statement building
	 *
	 * @param TokenStream $stream
	 * @param BuildContext $context
	 * @return \NoreSources\SQL\BuildContext
	 */
	public function finalize(TokenStream $stream, BuildContext &$context)
	{
		$context->sql = '';
		$context->initializeInputData(null);

		foreach ($stream as $token)
		{
			$value = $token[TokenStream::INDEX_TOKEN];
			$type = $token[TokenStream::INDEX_TYPE];

			if ($type == K::TOKEN_PARAMETER)
			{
				$value = strval($value);
				$position = $context->getParameterCount();
				$name = $this->getParameter($value, $position);
				$context->registerParameter($position, $value, $name);
				$value = $name;
			}

			$context->sql .= $value;
		}

		return $context;
	}

	/**
	 * GET the SQL keyword associated to the given foreign key action
	 *
	 * @param string $action
	 * @return string
	 */
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
