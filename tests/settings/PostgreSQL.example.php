<?php
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConnection;

/**
 * Connection settings for PostgreSQL
 */

return [
	K::CONNECTION_TYPE => PostgreSQLConnection::class
];