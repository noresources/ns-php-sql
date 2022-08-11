ns-php-sql
===========

*THIS PROJECT IS ABANDONED*. See reasons bolow.

SQL abstraction layer for SQL engine and SQL language dialects.

# Features

* Data structure description
 * Abstract schemal oadable from XML schema
 * Automatic DMBS type mapping
* DBMS Data structure manipulation and query
 * Retrieve data structure description from DBMS
 * Create DBMS structure from abstract data structure
 * Add, remove or modify existing DBMS structure
 * Update DBMS structure from differences with abstract data structure description
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

# Reason for abandoning this project

`ns-php-sql` 2.0 was started to provide a clean, elegant,
simple but powerful abstraction layer over the
common DBMS systems. I was not satisfied by the API and/or internal design
of the most popular existing projects.

After three years of peaceful development, I came to the conclusion
that I will not be able to spend enough time to achieve my goals.
The number of compromise, tweaks and hacks required to have a decent unified
solution for "only" three different systems is astonishing. DBMS internal 
constraints and SQL dialects differ so much that implementing a single feature
could lead to a never ending story.




