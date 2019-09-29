<?php

// NAmespace
namespace NoreSources\SQL\SQLite;

class Constants extends \NoreSources\SQL\Constants
{

	/**
	 * SQLite file encryption key
	 *
	 * @var string
	 */
	const CONNECTION_PARAMETER_ENCRYPTION_KEY = 'encryptionkey';

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