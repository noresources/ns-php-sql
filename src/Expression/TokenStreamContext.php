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

use NoreSources\SQL\Statement\OutputData;
use NoreSources\SQL\Statement\StatementBuilderAwareInterface;
use NoreSources\SQL\Structure\StructureResolverInterface;

interface TokenStreamContext extends StructureResolverInterface, StatementBuilderAwareInterface,
	OutputData
{

	/**
	 * Set the statement type
	 *
	 * @param integer $type
	 */
	function setStatementType($type);

	/**
	 * Set a SELECT statement result column
	 *
	 * @param integer $index
	 * @param integer|ColumnStructure $data
	 *
	 * @note A result column can only be set on top-level context
	 */
	function setResultColumn($index, $data);
}