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

use NoreSources\SQL\DataSerializer;
use NoreSources\SQL\Expression\MetaFunctionCall;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContext;
use NoreSources\SQL\Structure\ColumnPropertyMap;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\StructureElement;
use NoreSources\SQL\Structure\TableConstraint;
use NoreSources\SQL\Structure\TableStructure;

/**
 * Build a SQL statement string to be used in a SQL engine
 */
interface StatementBuilderInterface extends DataSerializer
{

	/**
	 *
	 * @return number
	 */
	function getBuilderFlags($domain = K::BUILDER_DOMAIN_GENERIC);

	/**
	 * Escape text string to be inserted in a SQL statement.
	 *
	 * @param string $value
	 *        	A quoted string with escaped characters
	 */
	function serializeString($value);

	/**
	 * Escape binary data to be inserted in a SQL statement.
	 *
	 * @param mixed $value
	 * @return string
	 */
	function serializeBinary($value);

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
	 * @param ParameterMap $parameters
	 *        	The already assigned parameters
	 *
	 *        	On some DBs,MS implementations, @c null may not be accepted as a value for @c $arameters argument
	 */
	function getParameter($name, ParameterMap $parameters = null);

	/**
	 *
	 * @param ColumnStructure $column
	 * @return TypeInterface
	 */
	function getColumnType(ColumnStructure $column);

	/**
	 * Get the closest DBMS type name for a given data type
	 *
	 * @param ColumnStructure $column
	 *        	Column definition
	 * @return string The default Connection type name for the given data type
	 */
	function getColumnTypeName(ColumnStructure $column);

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
	function getTimestampFormat($type = 0);

	/**
	 * Build a partial SQL statement describing a table constraint in a CREATE TABLE statement.
	 *
	 * @param TableStructure $structure
	 * @param TableConstraint $constraint
	 * @return string
	 */
	function getTableConstraintDescription(TableStructure $structure, TableConstraint $constraint);

	function serializeColumnData(ColumnPropertyMap $column, $value);

	/**
	 *
	 * @param array $path
	 * @return string
	 */
	function escapeIdentifierPath($path);

	/**
	 *
	 * @param StructureElement $structure
	 * @return string
	 */
	function getCanonicalName(StructureElement $structure);

	/**
	 * GET the SQL keyword associated to the given foreign key action
	 *
	 * @param string $action
	 * @return string
	 */
	function getForeignKeyAction($action);

	/**
	 * Postprocess statement token stream
	 *
	 * @param TokenStream $stream
	 *        	Token stream filled with Statement::tokenize() method
	 * @param TokenStreamContext $context
	 *        	Token stream context used during tokenization
	 *
	 * @return StatementData Statement SQL string, type, result columns and parameters
	 */
	function finalizeStatement(TokenStream $stream, TokenStreamContext &$context);
}