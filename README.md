ns-php-sql
===========

SQL abstraction layer for SQL engine and SQL language


# Terminology
* Datasource: A connection to a set of data on a SQL engine
  * MySQL: A connection to a MySQL server
  * SQLite: A connection to one or more SQLite database file
  * Postgres: A connection to a Postgres server on a given Postgres database
* TableSet: The SQL object which holds a set of tables
  * MySQL: A MySQL DATABASE
  * SQLite: A SQLite file
  * Postgres: A Postgres SCHEMA
* Table: A SQL table