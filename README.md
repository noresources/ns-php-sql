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

# Expression
## Timestamp

Timestamp must follow the ISO 8601 format with the following restrictions

* The midnight notation 24:00 is not supported
* Fractional time is only accepted for seconds   