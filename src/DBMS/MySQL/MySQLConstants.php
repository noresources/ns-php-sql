<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\SQL\Constants;

class MySQLConstants extends Constants
{

	/**
	 * Default database
	 *
	 * On connection, use the given database.
	 *
	 * @var string
	 * @see https://mariadb.com/kb/en/use/
	 */
	const CONNECTION_DEFAULT_NAMESPACE = 'mysqldefaultdatabase';

	/**
	 * Connection protocol type.
	 *
	 * Accepted values are
	 * <ul>
	 * <li>CONNECTION_PROTOCOL_TCP</li>
	 * <li>CONNECTION_PROTOCOL_SOCKET</li>
	 * <li>CONNECTION_PROTOCOL_PIPE</li>
	 * </ul>
	 */
	const CONNECTION_PROTOCOL = 'mysqlprotocol';

	/**
	 * Use TCP to connect to server
	 */
	const CONNECTION_PROTOCOL_TCP = 'mysqlprotocoltcp';

	/**
	 * Use socket to connect to server
	 */
	const CONNECTION_PROTOCOL_SOCKET = 'mysqlprotocolsocket';

	/**
	 * Use pipe to connect to server
	 */
	const CONNECTION_PROTOCOL_PIPE = 'mysqlprotocolpipe';

	/**
	 * The maximum size of a key column (in bytes)
	 *
	 * @var integer
	 */
	const KEY_MAX_LENGTH = 767;
}