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
use NoreSources as ns;

class Helper
{

	public static function getExpressionDataType($expression)
	{
		if ($expression instanceof ExpressionReturnType)
		{
			return $expression->getExpressionDataType();
		}

		if ($expression instanceof \DateTime)
			return K::DATATYPE_TIMESTAMP;
		elseif (\is_integer($expression))
			return K::DATATYPE_INTEGER;
		elseif (\is_float($expression))
			return K::DATATYPE_NUMBER;
		elseif (\is_bool($expression))
			return K::DATATYPE_BOOLEAN;
		elseif (\is_null($expression))
			return K::DATATYPE_NULL;
		elseif (ns\TypeDescription::hasStringRepresentation($expression))
			return K::DATATYPE_STRING;

		return K::DATATYPE_UNDEFINED;
	}
}