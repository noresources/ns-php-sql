<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SQL as sql;
use NoreSources\Expression as xpr;

interface ExpressionReturnType
{

	/**
	 *
	 * @return integer
	 */
	function getExpressionDataType();
}