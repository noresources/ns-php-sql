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

use NoreSources\SQL\DataSerializerInterface;
use NoreSources\SQL\Expression\MetaFunctionCall;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Structure\ColumnStructure;

/**
 * Build a SQL statement string to be used in a SQL engine
 */
interface StatementBuilderInterface extends DataSerializerInterface
{

	/**
	 *
	 * @return number
	 */
	function getBuilderFlags($domain = K::BUILDER_DOMAIN_GENERIC);

	/**
	 * Escape SQL identifier to be inserted in a SQL statement.
	 *
	 * @param string $identifier
	 */
	function escapeIdentifier($identifier);

	/**
	 *
	 * Get a DBMS-compliant parameter name
	 *
	 * @param string $name
	 *        	Parameter name
	 * @param ParameterData $parameters
	 *        	The already assigned parameters
	 *
	 *        	NULL may be passed when the builder does not require the
	 *        	previou
	 */
	function getParameter($name, ParameterData $parameters = null);

	/**
	 *
	 * @param ColumnStructure $column
	 * @return TypeInterface
	 */
	function getColumnType(ColumnStructure $column);

	/**
	 * Translate a meta function to a DBMS compliant function
	 *
	 * @param MetaFunctionCall $metaFunction
	 * @return \NoreSources\SQL\Expression\FunctionCall
	 */
	function translateFunction(MetaFunctionCall $metaFunction);

	/**
	 * Get syntax keyword.
	 *
	 * @param integer $keyword
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	function getKeyword($keyword);

	/**
	 *
	 * @param integer $joinTypeFlags
	 *        	JOIN type flags
	 * @return string
	 */
	function getJoinOperator($joinTypeFlags);

	/**
	 * Get the \DateTime timestamp format accepted by the ConnectionInterface
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
	function getTimestampFormat($type = 0);

	/**
	 *
	 * @param StructureElementInterface|\Traversable|string $structure
	 * @return string
	 */
	function getCanonicalName($structure);

	/**
	 * GET the SQL keyword associated to the given foreign key action
	 *
	 * @param string $action
	 * @return string
	 */
	function getForeignKeyAction($action);

	/**
	 *
	 * @param TokenStream $stream
	 *        	A token stream containing statement tokens
	 * @param TokenStreamContextInterface $context
	 *        	The stream context used to fill the token stream
	 * @return StatementData
	 */
	function finalizeStatement(TokenStream $stream, TokenStreamContextInterface &$context);
}