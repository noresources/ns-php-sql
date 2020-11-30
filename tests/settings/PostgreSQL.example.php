<?php
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConnection;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConstants as K;

/**
 * ConnectionInterface settings for PostgreSQL
 */

return [
	K::CONNECTION_TYPE => PostgreSQLConnection::class
];