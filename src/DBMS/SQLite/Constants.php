<?php
/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\SQLite;

class Constants extends \NoreSources\SQL\Constants
{

	/**
	 * SQLite file encryption key
	 *
	 * @var string
	 */
	const CONNECTION_PARAMETER_ENCRYPTION_KEY = 'encryptionkey';

	/**
	 * An associative array of SQLite pragma and their values
	 *
	 * @seeconst CONNECTION_PARAMETER_ENCRYPTION_KEY = 'encryptionkey';
	 *
	 * @var string
	 */
	const CONNECTION_PARAMETER_SQLITE_PRAGMAS = 'pragmas';

	/**
	 * ROLLBACK conflict class
	 *
	 * @var string
	 * @see https://sqlite.org/lang_conflict.html
	 */
	const CONFLICT_ACTION_ROLLBACK = 'rollback';

	/**
	 * ABORT conflict class
	 *
	 * @var string
	 * @see https://sqlite.org/lang_conflict.html
	 */
	const CONFLICT_ACTION_ABORT = 'abort';

	/**
	 * FAIL conflict class
	 *
	 * @var string
	 * @see https://sqlite.org/lang_conflict.html
	 */
	const CONFLICT_ACTION_FAIL = 'fail';

	/**
	 * IGNORE conflict class
	 *
	 * @var string
	 * @see https://sqlite.org/lang_conflict.html
	 */
	const CONFLICT_ACTION_IGNORE = 'ignore';

	/**
	 * REPLACEconflict class
	 *
	 * @var string
	 * @see https://sqlite.org/lang_conflict.html
	 */
	const CONFLICT_ACTION_REPLACE = 'replace';
}