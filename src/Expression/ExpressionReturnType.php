<?php
/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Expression;

interface ExpressionReturnType
{

	/**
	 *
	 * @return integer
	 */
	function getExpressionDataType();
}