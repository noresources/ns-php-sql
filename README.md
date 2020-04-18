ns-php-sql
===========

SQL abstraction layer for SQL engine and SQL language

# Features

* Abstract data structure description system
 * Loadable from XML schema 
* Easy and powerful DBMS-independent statement building using literal expression and/or polish notation
* Accurate translation to DBMS dialect
 * DBMS-specific query syntax (ex. SQLite CREATE TABLE primary key syntax)
 * Value formatting
 * Identifier escaping (ex. backquote for MySQL, double quotes for PostgreSQL) 
 * Keyword name variations (ex. `AUTO INCREMENT`, `AUTOINCREMENT`, `AUTO_INCREMENT`)  
* SQLite, PostgreSQL and MySQL/MariaDB support as well as PDO (less accurate)

