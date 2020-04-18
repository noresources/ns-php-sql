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

/**
 * Describe the kind of data type an expression will represents
 */
interface ExpressionReturnTypeInterface
{

	/**
	 *
	 * @return integer
	 */
	function getExpressionDataType();
}