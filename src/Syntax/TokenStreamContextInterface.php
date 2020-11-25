<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

use NoreSources\SQL\DBMS\PlatformProviderInterface;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\StructureResolverInterface;
use NoreSources\SQL\Syntax\Statement\ParameterDataProviderInterface;
use NoreSources\SQL\Syntax\Statement\ResultColumnProviderInterface;
use NoreSources\SQL\Syntax\Statement\StatementTypeProviderInterface;

/**
 * Statement tokenization context
 */
interface TokenStreamContextInterface extends
	StructureResolverInterface, ParameterDataProviderInterface,
	StatementTypeProviderInterface, ResultColumnProviderInterface,
	PlatformProviderInterface
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
	 * @param string|null $as
	 *        	Column name. If null, use $data
	 *
	 * @note A result column can only be set on top-level context
	 */
	function setResultColumn($index, $data, $as = null);
}