ns-php-sql
===========

SQL abstraction layer for SQL engine and SQL language dialects.

# Features

* Data structure description
 * Abstract schemal oadable from XML schema
 * Automatic DMBS type mapping
* SQL statement building
  * Easy and powerful DBMS-independent statement building using literal expression and/or polish notation
  * Accurate translation to DBMS dialect
  * Automatic value formatting according table column properties
* DBMS drivers
  * Prepare statements
  * Execute statement with/without parameters
  * Automatic column value deserialization of query results
* Native support of common DBMS
  * SQLite
  * PostgreSQL
  * MySQL/MariaDB
  * PDO (less accurate)

