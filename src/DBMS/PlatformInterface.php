<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\SemanticVersion;
use NoreSources\Expression\ExpressionInterface;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Configuration\ConfiguratorInterface;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Syntax\FunctionCall;
use NoreSources\SQL\Syntax\MetaFunctionCall;
use NoreSources\SQL\Syntax\Statement\ParameterData;
use NoreSources\SQL\Syntax\Statement\StatementFactoryInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * DBMS feature support informations, SQL dialect translation
 */
interface PlatformInterface extends LoggerAwareInterface,
	DataSerializerInterface, StringSerializerInterface,
	DataUnserializerInterface, BinaryDataSerializerInterface,
	IdentifierSerializerInterface, StatementFactoryInterface
{

	/**
	 * Query feature support
	 *
	 * @param mixed $query
	 * @param mixed $dflt
	 *        	Default value if $query is not found. If NULL, let the Platform choose the most
	 *        	meaningful default value
	 */
	function queryFeature($query, $dflt = null);

	/**
	 * Create a runtime connection configurator object.
	 *
	 * This interface should only be used internally.
	 *
	 * @param ConnectionInterface $connection
	 *        	A DBMS connection compatible with the platform
	 * @return ConfiguratorInterface
	 */
	function newConfigurator(ConnectionInterface $connection);

	/**
	 *
	 * @param string $kind
	 * @return SemanticVersion
	 */
	function getPlatformVersion($kind = self::VERSION_CURRENT);

	/**
	 *
	 * @param mixed $columnDescription
	 *        	Column description
	 * @param number $constraintFlags
	 *        	Column constraint flags
	 * @return TypeInterface
	 */
	function getColumnType($columnDescription, $constraintFlags = 0);

	/**
	 *
	 * @return TypeRegistry
	 */
	function getTypeRegistry();

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
	function getParameter($name, $valueDataType = K::DATATYPE_UNDEFINED,
		ParameterData $parameters = null);

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
	 * Get the SQL keyword associated to the given foreign key action
	 *
	 * @param string $action
	 * @return string|NULL Action keyword or NULL if the action is not supported
	 */
	function getForeignKeyAction($action);

	/**
	 * Get the \DateTime timestamp format accepted by the ConnectionInterface
	 *
	 * @param integer $dataType
	 *        	Timestamp parts. Combination of
	 *        	<ul>
	 *        	<li>Constants\DATATYPE_DATE</li>
	 *        	<li>Constants\DATATYPE_TIME</li>
	 *        	<li>Constants\DATATYPE_TIMEZONE</li>
	 *        	</ul>
	 *
	 * @return string \DateTime format string
	 */
	function getTimestampTypeStringFormat($dataType = 0);

	/**
	 * Indicates if the platform fully supports the given timestamp data type
	 *
	 * @param integer $dataType
	 *        	Timestamp data type flags
	 * @return boolean TRUE if all parts of the given data type flags
	 *         can be represented.
	 */
	function hasTimestampTypeStringFormat($dataType);

	/**
	 *
	 * @param string $formatToken
	 *        	One of the PHP date format string token. @see
	 *        	https://www.php.net/manual/en/datetime.format.php
	 * @throws \InvalidArgumentException if $formatToken is not a valid PHP date token
	 * @param false|string|array|NULL $formatToken
	 *        	If $formatToken is a valid PHP date format token:
	 *        	<ol>
	 *        	<li>The DBMS string translation for the given token if the DBMS provides the exact
	 *        	translation.</li>
	 *        	<li>A two element array containing an acceptable fallback translation and the
	 *        	fallback
	 *        	detail if the DBMS cannot provide an exact translation</li>
	 *        	<li>false if no translation is available</li>
	 *        	<li>NULL if $formatToken is not a valid PHP date format token</li>
	 *        	</ol>
	 */
	function getTimestampFormatTokenTranslation($formatToken);

	/**
	 * Transform anything to a literal value of the given data type.
	 *
	 * @param mixed $value
	 * @param integer $dataType
	 *        	Data type. If NULL, the data type will be obtained from the $value parameter
	 * @return mixed A literal value
	 */
	function literalize($value, $dataType = null);

	/**
	 *
	 * @param string $baseClassname
	 * @param mixed[] ...$arguments
	 *        	Expression class constructor arguments
	 * @return ExpressionInterface
	 */
	function newExpression($baseClassname, ...$arguments);

	/**
	 *
	 * @param MetaFunctionCall $metaFunction
	 *
	 * @return FunctionCall
	 */
	function translateFunction(MetaFunctionCall $metaFunction);

	/**
	 *
	 * @param StructureElementInterface|array|string $path
	 * @return string
	 */
	function quoteIdentifierPath($path);

	const STRUCTURE_FILENAME_FACTORY = K::PLATFORM_STRUCTURE_FILENAME_FACTORY;

	const VERSION_CURRENT = K::PLATFORM_VERSION_CURRENT;

	const VERSION_COMPATIBILITY = K::PLATFORM_VERSION_COMPATIBILITY;
}
