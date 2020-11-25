<?php
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\PDO\PDOConnection;

/**
 * ConnectionInterface settings for PDO
 */

return [
	K::CONNECTION_TYPE => PDOConnection::class,
	K::CONNECTION_SOURCE => [
		'sqlite',
		'filepath'
	]
];