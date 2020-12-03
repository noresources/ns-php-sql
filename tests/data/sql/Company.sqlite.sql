-- ns-xml database schema to SQL translation
-- Database main
-- Company employees
CREATE TABLE "main"."Employees"
(
"id" INTEGER NOT NULL PRIMARY KEY,
"name" TEXT,
"gender" TEXT(1),
"salary" REAL(7,2)
);
CREATE INDEX "main"."index_employees_name" ON "Employees" ("name");
CREATE TABLE "main"."Hierarchy"
(
"managerId" INTEGER NOT NULL,
"manageeId" INTEGER NOT NULL,
PRIMARY KEY ("managerId", "manageeId"),
FOREIGN KEY ("managerId") REFERENCES "Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE, 
FOREIGN KEY ("manageeId") REFERENCES "Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE
);
CREATE TABLE "main"."Tasks"
(
"id" INTEGER PRIMARY KEY AUTOINCREMENT,
"name" TEXT(32),
-- Creation timestamp
"creationDateTime" TEXT DEFAULT CURRENT_TIMESTAMP,
"priority" INTEGER,
"category" INTEGER,
"creator" INTEGER DEFAULT NULL,
"assignedTo" INTEGER DEFAULT NULL,
CONSTRAINT "fk_creator" FOREIGN KEY ("creator") REFERENCES "Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE, 
FOREIGN KEY ("assignedTo") REFERENCES "Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE
);
CREATE TABLE "main"."types"
(
"base",
"binary" BLOB DEFAULT X'616263',
"boolean" NUMERIC DEFAULT true,
"int" INTEGER PRIMARY KEY AUTOINCREMENT,
-- A large int with size spec.
"large_int" INTEGER(12) DEFAULT 123456789012,
-- A quite small int with size spec.
"small_int" INTEGER(3),
"float" REAL DEFAULT 1.23,
"timestamp" TEXT DEFAULT '2010-11-12T13:14:15+01:00',
"timestamp_tz" TEXT DEFAULT '2010-11-12T13:14:15+01:00'
);
