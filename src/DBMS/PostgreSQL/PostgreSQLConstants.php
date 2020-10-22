<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\PostgreSQL;

use NoreSources\SQL\Constants;

/**
 * PostgreSQL specific constants
 */
class PostgreSQLConstants extends Constants
{

	/**
	 * PostgreSQL-specific connection parameters
	 * Value is expected to be an associative array or a string
	 *
	 * @see https://www.postgresql.org/docs/10/libpq-connect.html#LIBPQ-PARAMKEYWORDS
	 * @var string
	 */
	const CONNECTION_PGSQL = 'pgsq-specific';
}