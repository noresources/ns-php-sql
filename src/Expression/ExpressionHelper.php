<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Expression;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\ColumnStructure;

class ExpressionHelper
{

	/**
	 * Create Column
	 *
	 * @param string|ColumnStructure $column
	 * @return \NoreSources\SQL\Expression\Column
	 */
	public static function column($column)
	{
		if ($column instanceof ColumnStructure)
		{
			$column = $column->getPath();
		}

		return new Column($column);
	}

	/**
	 * Create a Literal
	 *
	 * @param mixed $value
	 *        	Literal value
	 * @param integer|ColumnStructure $type
	 *        	Data type hint
	 *
	 * @return \NoreSources\SQL\Expression\Literal
	 */
	public static function literal($value, $type = K::DATATYPE_UNDEFINED)
	{
		if ($type instanceof ColumnStructure)
		{
			$type = $type->getColumnProperty(K::COLUMN_DATA_TYPE);
		}

		return new Literal($value, $type);
	}

	/**
	 *
	 * @param string $name
	 *        	Parameter name
	 *
	 * @return \NoreSources\SQL\Expression\Parameter
	 */
	public static function parameter($name)
	{
		return new Parameter($name);
	}

	/**
	 *
	 * @param mixed $expression
	 *        	Any object
	 * @return integer Data type identifier
	 */
	public static function getExpressionDataType($expression)
	{
		if ($expression instanceof ExpressionReturnTypeInterface)
		{
			return $expression->getExpressionDataType();
		}

		return Literal::dataTypeFromValue($expression);
	}
}