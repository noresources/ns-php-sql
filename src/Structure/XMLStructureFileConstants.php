<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\Constants;

class XMLStructureFileConstants extends Constants
{

	/**
	 * Expected DateTime format for the XML schema definition "dateTime" built-in type
	 *
	 * @var string
	 */
	const XML_DATETIME_FORMAT = 'Y-m-d\TH:i:sP';

	/**
	 * SQL structure description XML namespace URI prefix.
	 *
	 * SQL schema version should be appended to it.
	 *
	 * @var string
	 */
	const XML_NAMESPACE_BASEURI = 'http://xsd.nore.fr/sql';

	/**
	 * The XML namespace prefix used internally to reference the
	 * SQL structure description XML schema
	 *
	 * @var string
	 */
	const XML_NAMESPACE_PREFIX = 'sql';

	/**
	 * Index XML node name
	 *
	 * @var string
	 */
	const XML_ELEMENT_UBDEX = 'index';
}