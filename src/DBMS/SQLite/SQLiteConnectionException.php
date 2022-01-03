<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\SQL\DBMS\ConnectionException;

class SQLiteConnectionException extends ConnectionException
{

	public function __construct(SQLiteConnection $connection = null,
		$message, $code = null)
	{
		if ($code === null && ($connection instanceof SQLiteConnection))
		{
			$code = $connection->sqliteConnection->lastErrorCode();
			if ($code != 0)
				$message .= ' (' .
					$connection->sqliteConnection->lastErrorMsg() . ')';
		}
		parent::__construct($connection, $message, $code);
	}
}