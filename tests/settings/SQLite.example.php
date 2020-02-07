<?php
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\SQLite\SQLiteConnection;

/**
 * Connection settings for PostgreSQL
 */

return [
	K::CONNECTION_PARAMETER_TYPE => SQLiteConnection::class,
	K::CONNECTION_PARAMETER_SOURCE => [
		'ns_unittests' => __DIR__ . '/../derived/sqlite_test_database.sqlite'
	],
	K::CONNECTION_PARAMETER_CREATE => true
];