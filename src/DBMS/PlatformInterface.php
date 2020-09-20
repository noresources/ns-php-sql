<?php
namespace NoreSources\SQL\DBMS;

use NoreSources\SemanticVersion;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\FunctionCall;
use NoreSources\SQL\Expression\MetaFunctionCall;
use Psr\Log\LoggerAwareInterface;

interface PlatformInterface extends FeatureQueryInterface,
	LoggerAwareInterface
{

	/**
	 *
	 * @param string $kind
	 * @return SemanticVersion
	 */
	function getPlatformVersion($kind = self::VERSION_CURRENT);

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
	 * GET the SQL keyword associated to the given foreign key action
	 *
	 * @param string $action
	 * @return string
	 */
	function getForeignKeyAction($action);

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
	function getTimestampTypeStringFormat($type = 0);

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
	 *
	 * @param MetaFunctionCall $metaFunction
	 *
	 * @return FunctionCall
	 */
	function translateFunction(MetaFunctionCall $metaFunction);

	const VERSION_CURRENT = K::PLATFORM_VERSION_CURRENT;

	const VERSION_COMPATIBILITY = K::PLATFORM_VERSION_COMPATIBILITY;

	const FEATURE_NAMESPACE = K::PLATFORM_FEATURE_NAMESPACE;

	const FEATURE_TABLE = K::PLATFORM_FEATURE_TABLE;

	const FEATURE_VIEW = K::PLATFORM_FEATURE_VIEW;

	const FEATURE_INDEX = K::PLATFORM_FEATURE_INDEX;

	const FEATURE_TRIGGER = K::PLATFORM_FEATURE_TRIGGER;

	const FEATURE_FUNCTION = K::PLATFORM_FEATURE_FUNCTION;

	const FEATURE_CREATE = K::PLATFORM_FEATURE_CREATE;

	const FEATURE_REPLACE = K::PLATFORM_FEATURE_REPLACE;

	const FEATURE_TEMPORARY = K::PLATFORM_FEATURE_TEMPORARY;

	const FEATURE_SCOPED = K::PLATFORM_FEATURE_SCOPED;

	const FEATURE_COLUMN_DECLARATION_FLAGS = K::PLATFORM_FEATURE_COLUMN_DECLARATION_FLAGS;

	const FEATURE_COLUMN_KEY_MANDATORY_LENGTH = K::PLATFORM_FEATURE_COLUMN_KEY_MANDATORY_LENGTH;

	const FEATURE_COLUMN_ENUM = K::PLATFORM_FEATURE_COLUMN_ENUM;

	const FEATURE_DROP = K::PLATFORM_FEATURE_DROP;

	const FEATURE_EXISTS_CONDITION = K::PLATFORM_FEATURE_EXISTS_CONDITION;

	const FEATURE_CASCADE = K::PLATFORM_FEATURE_CASCADE;

	const FEATURE_SELECT = K::PLATFORM_FEATURE_SELECT;

	const FEATURE_EXTENDED_RESULTCOLUMN_RESOLUTION = K::PLATFORM_FEATURE_EXTENDED_RESULTCOLUMN_RESOLUTION;

	const FEATURE_INSERT = K::PLATFORM_FEATURE_INSERT;

	const FEATURE_DEFAULTVALUES = K::PLATFORM_FEATURE_DEFAULTVALUES;

	const FEATURE_DEFAULT = K::PLATFORM_FEATURE_DEFAULT;

	const FEATURE_JOINS = K::PLATFORM_FEATURE_JOINS;

	const FEATURE_EVENT_ACTIONS = K::PLATFORM_FEATURE_EVENT_ACTIONS;
}
