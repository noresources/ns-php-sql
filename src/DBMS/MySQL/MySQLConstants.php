<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\SQL\Constants;

class MySQLConstants extends Constants
{

	/**
	 * Connection protocol type.
	 *
	 * Accepted values are
	 * <ul>
	 * <li>CONNECTION_PARAMETER_PROTOCOL_TCP</li>
	 * <li>CONNECTION_PARAMETER_PROTOCOL_SOCKET</li>
	 * <li>CONNECTION_PARAMETER_PROTOCOL_PIPE</li>
	 * </ul>
	 */
	const CONNECTION_PARAMETER_PROTOCOL = 'mysqlprotocol';

	/**
	 * Use TCP to connect to server
	 */
	const CONNECTION_PARAMETER_PROTOCOL_TCP = 'mysqlprotocoltcp';

	/**
	 * Use socket to connect to server
	 */
	const CONNECTION_PARAMETER_PROTOCOL_SOCKET = 'mysqlprotocolsocket';

	/**
	 * Use pipe to connect to server
	 */
	const CONNECTION_PARAMETER_PROTOCOL_PIPE = 'mysqlprotocolpipe';
}