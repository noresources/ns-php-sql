<?php

/**
 * Copyright © 2012-2015 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;
use NoreSources as ns;

if (!defined('NS_PHP_SQL_PATH'))
{
	define('NS_PHP_SQL_PATH', realpath(__DIR__));
}
const SQL_VERSION_MAJOR = 0;
const SQL_VERSION_MINOR = 1;
const SQL_VERSION_PATCH = 0;

/**
 * Version string of NoreSources XSLT module.
 * The version string can be used with the PHP function version_compare()
 * 
 * @return XSLT module version
 */
function sql_version_string()
{
	return (SQL_VERSION_MAJOR . '.' . SQL_VERSION_MINOR . '.' . SQL_VERSION_PATCH);
}

function sql_version_number()
{
	return (SQL_VERSION_MAJOR * 10000 + SQL_VERSION_MINOR * 100 + SQL_VERSION_PATCH);
}

/**
 * @defgroup structure 'Structure properties'
 * @{
 */

/**
 * The name of the Datasource field type
 */
const kStructureDatatype = 'Datasourcetype';
/**
 * The field is part of a primary key
 * @var unknown
 */
const kStructurePrimaryKey = 'primary';
/**
 * The field value is auto-incremented (integer field type) 
 */
const kStructureAutoincrement = 'auto';
/**
 * The field is indexed
 */
const kStructureIndexed = 'index';
/**
 * The field accepts null values
 */
const kStructureAcceptNull = 'null';
/**
 * Data size
 */
const kStructureDataSize = 'size';
/**
 * Number of decimals (numeric field types)
 */
const kStructureDecimalCount = 'decimalsize';
/**
 * List of valid values
 */
const kStructureEnumeration = 'valid_values';
/**
 * Default value
 */
const kStructureDefaultValue = 'default_value';


/**
 * The field accepts tuple values
 * @var unknown
 */
const kStructureAcceptMultipleValues = 'accept_multivalues';

// Datasource-relative / Application-relative properties

/**
 * The field typename (datasource dependant) 
 */
const kStructureFieldTypename = 'typename';

/**
 * FieldValidator classname to use before insertion
 */
const kStructureValidatorClassname = 'validator_classname';

/**
 * @}
 */
// group 'structure'

include_once (NS_PHP_SQL_PATH . '/sql.autoload.inc.php');
