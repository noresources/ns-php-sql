<?php
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\SQLite\SQLiteConnection;

/**
 * ConnectionInterface settings for PostgreSQL
 */

return [
	K::CONNECTION_TYPE => SQLiteConnection::class,
	K::CONNECTION_SOURCE => [
		'ns_unittests' => __DIR__ . '/../derived/sqlite_test_database.sqlite'
	],
	K::CONNECTION_CREATE => true
];